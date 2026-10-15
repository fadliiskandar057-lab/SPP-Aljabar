<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TagihanExemption;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyBillsService
{
    public function generate(int $tahunAjaranId, string $bulan, int $tahun, string $jatuhTempo, ?int $siswaId = null): array
    {
        return DB::transaction(function () use ($tahunAjaranId, $bulan, $tahun, $jatuhTempo, $siswaId) {
            $stats = ['created' => 0, 'free' => 0, 'discounted' => 0, 'skipped_existing' => 0, 'skipped_no_fee' => 0];
            $students = Siswa::with('kelas', 'feeRates', 'progressions')->where('status', 'aktif')
                ->when($siswaId !== null, fn ($query) => $query->whereKey($siswaId))->orderBy('id')->lockForUpdate()->get();
            // Calendar periods already paid or billed under another academic year must not be duplicated.
            $recorded = Tagihan::whereIn('siswa_id', $students->pluck('id'))->where('bulan', $bulan)->where('tahun', $tahun)
                ->lockForUpdate()->pluck('siswa_id')->flip();
            $reserved = Pembayaran::whereIn('siswa_id', $students->pluck('id'))->where('status', 'pending')
                ->whereNotNull('future_plan')->get()->filter(fn ($payment) => collect($payment->future_plan['items'])
                ->contains(fn ($item) => $item['bulan'] === $bulan && (int) $item['tahun'] === $tahun))->pluck('siswa_id')->flip();
            $exemptions = TagihanExemption::where('tahun_ajaran_id', $tahunAjaranId)->where('bulan', $bulan)->where('tahun', $tahun)->get();
            $resolver = app(StudentFeeResolver::class);
            $months = array_flip(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']);
            $period = Carbon::create($tahun, $months[$bulan] + 1, 1)->startOfMonth();
            $rows = [];
            foreach ($students as $student) {
                if ($recorded->has($student->id) || $reserved->has($student->id)) {
                    $stats['skipped_existing']++;

                    continue;
                }
                $base = $resolver->nominal($student, $tahunAjaranId, $period);
                if ($base === null) {
                    $stats['skipped_no_fee']++;

                    continue;
                }
                $class = $resolver->classAt($student, $period);
                $exemption = $this->matchingExemption($exemptions, $student, $class['id']);
                $nominal = $this->discountedNominal($base, $exemption);
                $rows[] = ['siswa_id' => $student->id, 'tahun_ajaran_id' => $tahunAjaranId, 'bulan' => $bulan,
                    'tahun' => $tahun, 'nominal' => $nominal, 'jatuh_tempo' => $jatuhTempo,
                    'status' => $nominal === 0 ? 'gratis' : 'belum_lunas', 'kelas_id' => $class['id'], 'kelas_nama' => $class['name'],
                    'created_at' => now(), 'updated_at' => now()];
                $stats['created']++;
                if ($nominal === 0) {
                    $stats['free']++;
                } elseif ($exemption) {
                    $stats['discounted']++;
                }
            }
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('tagihan')->insert($chunk);
            }

            return $stats;
        });
    }

    private function matchingExemption($exemptions, Siswa $siswa, int $classId): ?TagihanExemption
    {
        return $exemptions
            ->filter(function (TagihanExemption $exemption) use ($siswa, $classId) {
                return match ($exemption->scope_type) {
                    'siswa' => $exemption->siswa_id === $siswa->id,
                    'kelas' => $exemption->kelas_id === $classId,
                    default => true,
                };
            })
            ->sortByDesc(fn (TagihanExemption $exemption) => match ($exemption->scope_type) {
                'siswa' => 3,
                'kelas' => 2,
                default => 1,
            })
            ->first();
    }

    private function discountedNominal(int $nominal, ?TagihanExemption $exemption): int
    {
        if (! $exemption) {
            return $nominal;
        }

        return match ($exemption->benefit_type) {
            'free' => 0,
            'nominal' => max(0, $nominal - (int) $exemption->amount),
            'percent' => max(0, $nominal - (int) floor($nominal * min(100, (int) $exemption->amount) / 100)),
            default => $nominal,
        };
    }
}
