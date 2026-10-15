<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\StudentFeeRate;
use App\Models\StudentProgression;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentProgressionService
{
    public function __construct(private StudentFeeResolver $fees) {}

    public function preview(array $data): array
    {
        $effective = Carbon::createFromFormat('!Y-m', $data['effective_month']);
        if ($effective->gt(now()->startOfMonth()) || $data['from_year_id'] == $data['to_year_id']) {
            $this->reject('Tahun ajaran tujuan harus berbeda. Kenaikan berlaku langsung, pilih bulan efektif paling lambat bulan ini.');
        }
        $from = TahunAjaran::findOrFail($data['from_year_id']);
        $to = TahunAjaran::findOrFail($data['to_year_id']);
        if (preg_match('/^(\d{4})/', $from->nama, $a) && preg_match('/^(\d{4})/', $to->nama, $b) && $b[1] <= $a[1]) {
            $this->reject('Pilih tahun ajaran tujuan yang lebih baru daripada tahun asal.');
        }
        $classes = Kelas::all()->keyBy('id');
        $students = Siswa::with('kelas', 'feeRates', 'progressions')->whereIn('id', $data['student_ids'])->orderBy('id')->get();
        if ($students->count() !== count($data['student_ids'])) {
            $this->reject('Pilihan siswa berubah. Muat ulang daftar siswa.');
        }
        $rows = [];
        $pendingFuture = Pembayaran::whereIn('siswa_id', $data['student_ids'])->whereNotNull('future_plan')
            ->where('status', 'pending')->pluck('siswa_id')->flip();
        foreach ($students as $student) {
            $mapping = $data['classes'][$student->kelas_id] ?? ['action' => 'skip'];
            $exception = $data['exceptions'][$student->id] ?? [];
            if (filled($exception['action'] ?? null)) {
                $mapping = $exception;
            }
            $action = $mapping['action'] ?? 'skip';
            if ($action === 'skip') {
                continue;
            }
            if ($student->status !== 'aktif' || ($student->tahun_ajaran_id && $student->tahun_ajaran_id != $from->id)) {
                $this->reject("{$student->nama} tidak aktif atau sudah berada di tahun ajaran lain. Muat ulang pilihan siswa.");
            }
            if ($student->progressions->contains('to_year_id', $to->id)) {
                $this->reject("{$student->nama} sudah diproses untuk tahun ajaran {$to->nama}.");
            }
            $latest = $student->progressions->sortByDesc('effective_from')->first();
            if ($latest && $effective->lte($latest->effective_from)) {
                $this->reject('Bulan efektif harus sesudah perpindahan terakhir siswa.');
            }
            $target = null;
            if (in_array($action, ['lulus', 'keluar'], true) && $pendingFuture->has($student->id)) {
                $this->reject("{$student->nama} masih memiliki invoice masa depan pending. Selesaikan atau batalkan invoice sebelum kelulusan / keluar.");
            }
            if (in_array($action, ['naik', 'pindah', 'tetap'], true)) {
                $target = $action === 'tetap' ? $student->kelas : $classes->get($mapping['target_class_id'] ?? null);
                if (! $target) {
                    $this->reject("Pilih kelas tujuan untuk {$student->nama}.");
                }
                if ($action === 'naik' && ($target->id === $student->kelas_id ||
                    ($student->kelas->tingkat && $target->tingkat && $target->tingkat !== $student->kelas->tingkat + 1))) {
                    $this->reject('Kenaikan harus ke tingkat berikutnya. Gunakan Pindah kelas untuk tujuan lain.');
                }
            }
            $oldFee = $this->fees->nominal($student, $from->id, $effective->copy()->subMonth());
            $newFee = filled($mapping['nominal'] ?? null) ? (int) $mapping['nominal'] : $oldFee;
            if ($target && $newFee === null) {
                $this->reject("SPP sebelumnya untuk {$student->nama} belum tersedia. Isi nominal baru.");
            }
            $rows[] = ['id' => $student->id, 'nis' => $student->nis, 'name' => $student->nama, 'angkatan' => $student->angkatan,
                'from_class_id' => $student->kelas_id, 'from_class_name' => $student->kelas->nama_kelas,
                'from_year_id' => $student->tahun_ajaran_id, 'status' => $student->status, 'updated_at' => (string) $student->updated_at,
                'action' => $action, 'to_class_id' => $target?->id, 'to_class_name' => $target?->nama_kelas,
                'old_nominal' => $oldFee, 'nominal' => $target ? $newFee : null];
        }
        if ($rows === []) {
            $this->reject('Tidak ada siswa yang dipilih untuk diproses. Pilih tindakan kelas atau siswa.');
        }

        return ['input' => $data, 'rows' => $rows, 'from_year' => $from->nama, 'to_year' => $to->nama,
            'effective_from' => $effective->toDateString(), 'fingerprint' => hash('sha256', json_encode($rows))];
    }

    public function confirm(array $snapshot, User $actor): int
    {
        return DB::transaction(function () use ($snapshot, $actor) {
            Siswa::whereIn('id', $snapshot['input']['student_ids'])->orderBy('id')->lockForUpdate()->get();
            $current = $this->preview($snapshot['input']);
            if (! hash_equals($snapshot['fingerprint'], $current['fingerprint'])) {
                $this->reject('Data siswa atau tarif sudah berubah sejak pratinjau. Buat pratinjau baru.');
            }
            $effective = Carbon::parse($snapshot['effective_from']);
            $histories = $rates = $groups = [];
            foreach ($current['rows'] as $row) {
                $histories[] = [
                    'siswa_id' => $row['id'], 'from_year_id' => $snapshot['input']['from_year_id'],
                    'to_year_id' => $snapshot['input']['to_year_id'], 'from_class_id' => $row['from_class_id'],
                    'to_class_id' => $row['to_class_id'], 'from_class_name' => $row['from_class_name'],
                    'to_class_name' => $row['to_class_name'], 'action' => $row['action'],
                    'effective_from' => $effective->toDateString(), 'processed_by' => $actor->id,
                    'created_at' => now(), 'updated_at' => now(),
                ];
                $inactive = in_array($row['action'], ['lulus', 'keluar'], true);
                $updates = [
                    'kelas_id' => $row['to_class_id'] ?? $row['from_class_id'],
                    'tahun_ajaran_id' => $snapshot['input']['to_year_id'],
                    'status' => $inactive ? $row['action'] : 'aktif', 'inactive_since' => $inactive ? $effective->toDateString() : null,
                ];
                $key = $updates['kelas_id'].'|'.$updates['status'];
                $groups[$key]['updates'] = $updates;
                $groups[$key]['ids'][] = $row['id'];
                if (! $inactive) {
                    $rates[] = ['siswa_id' => $row['id'], 'effective_from' => $effective->toDateString(),
                        'tahun_ajaran_id' => $snapshot['input']['to_year_id'], 'semester' => $effective->month >= 7 ? 'ganjil' : 'genap',
                        'nominal' => $row['nominal'], 'set_by' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
                    ];
                }
            }
            foreach (array_chunk($histories, 200) as $chunk) {
                DB::table('student_progressions')->insert($chunk);
            }
            foreach (array_chunk($rates, 200) as $chunk) {
                StudentFeeRate::upsert($chunk, ['siswa_id', 'effective_from'], ['tahun_ajaran_id', 'semester', 'nominal', 'set_by', 'updated_at']);
            }
            foreach ($groups as $group) {
                Siswa::whereIn('id', $group['ids'])->update($group['updates']);
            }
            $firstHistory = StudentProgression::where('siswa_id', $current['rows'][0]['id'])
                ->where('to_year_id', $snapshot['input']['to_year_id'])->firstOrFail();
            app(ActivityLogger::class)->record($actor, 'student.progression', $firstHistory,
                count($current['rows']).' siswa diproses ke tahun ajaran '.$current['to_year'].'.',
                ['rows' => $current['rows'], 'effective_from' => $current['effective_from']], null, false);

            return count($current['rows']);
        });
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['progression' => $message]);
    }
}
