<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\StudentFeeRate;
use App\Models\StudentProgression;
use App\Models\TahunAjaran;
use App\Services\StudentProgressionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentProgressionController extends Controller
{
    public function index()
    {
        return view('admin.progression', [
            'classes' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(),
            'years' => TahunAjaran::orderByDesc('id')->get(),
            'students' => Siswa::with('kelas', 'tahunAjaran')->where('status', 'aktif')->orderBy('kelas_id')->orderBy('nama')->get(),
            'history' => StudentProgression::with('siswa', 'targetYear', 'processor')->latest()->paginate(20, ['*'], 'history_page'),
            'rates' => StudentFeeRate::with('siswa', 'tahunAjaran')->latest('effective_from')->limit(30)->get(),
        ]);
    }

    public function preview(Request $request, StudentProgressionService $service)
    {
        $this->decodeBatch($request);
        $rules = [
            'from_year_id' => ['required', 'exists:tahun_ajaran,id'], 'to_year_id' => ['required', 'exists:tahun_ajaran,id'],
            'effective_month' => ['required', 'date_format:Y-m'],
            'student_ids' => ['required', 'array', 'min:1', 'exists:siswa,id'], 'student_ids.*' => ['integer', 'distinct'],
            'classes' => ['required', 'array'], 'exceptions' => ['nullable', 'array'],
        ];
        foreach (['classes', 'exceptions'] as $group) {
            $rules[$group.'.*.action'] = [$group === 'classes' ? 'required' : 'nullable', 'in:naik,tetap,pindah,lulus,keluar,skip'];
            $rules[$group.'.*.target_class_id'] = ['nullable', 'exists:kelas,id'];
            $rules[$group.'.*.nominal'] = ['nullable', 'integer', 'min:0', 'max:4294967295'];
        }
        $plan = $service->preview($request->validate($rules));
        $token = (string) Str::uuid();
        // A single pending preview per admin session avoids accumulating large snapshots.
        $request->session()->put('student_progression', ['token' => $token, 'plan' => $plan]);

        return view('admin.progression_preview', compact('plan', 'token'));
    }

    public function confirm(Request $request, StudentProgressionService $service)
    {
        $request->validate(['token' => ['required', 'uuid']]);
        $preview = $request->session()->get('student_progression');
        if (! $preview || ! hash_equals($preview['token'], $request->string('token')->toString())) {
            return redirect()->route('admin.progression.index')->withErrors(['progression' => 'Pratinjau sudah diproses atau tidak tersedia. Buat pratinjau baru.']);
        }
        $count = $service->confirm($preview['plan'], $request->user());
        $request->session()->forget('student_progression');

        return redirect()->route('admin.progression.index')->with('success', "{$count} siswa berhasil diproses. Riwayat dan tarif SPP tersimpan.");
    }

    public function storeRates(Request $request)
    {
        $this->decodeBatch($request);
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1', 'exists:siswa,id'], 'student_ids.*' => ['required', 'integer', 'distinct'],
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'], 'semester' => ['required', 'in:ganjil,genap'],
            'effective_month' => ['required', 'date_format:Y-m'], 'nominal' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ]);
        $effective = Carbon::createFromFormat('!Y-m', $data['effective_month']);
        DB::transaction(function () use ($data, $effective, $request) {
            $students = Siswa::whereIn('id', $data['student_ids'])->orderBy('id')->lockForUpdate()->get();
            if ($students->contains(fn ($student) => $student->status !== 'aktif')) {
                throw ValidationException::withMessages(['rates' => 'Tarif baru hanya untuk siswa aktif.']);
            }
            $rates = [];
            foreach ($students as $student) {
                $rates[] = ['siswa_id' => $student->id, 'effective_from' => $effective->toDateString(),
                    'tahun_ajaran_id' => $data['tahun_ajaran_id'], 'semester' => $data['semester'],
                    'nominal' => $data['nominal'], 'set_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),
                ];
            }
            foreach (array_chunk($rates, 200) as $chunk) {
                StudentFeeRate::upsert($chunk, ['siswa_id', 'effective_from'], ['tahun_ajaran_id', 'semester', 'nominal', 'set_by', 'updated_at']);
            }
        });

        return redirect()->route('admin.progression.index')->with('success', 'Tarif semester tersimpan. Nominal tagihan yang sudah tercatat tetap sama.');
    }

    private function decodeBatch(Request $request): void
    {
        if ($request->has('batch_payload')) {
            $request->validate(['batch_payload' => ['required', 'json']]);
            $decoded = json_decode($request->input('batch_payload'), true);
            if (! is_array($decoded)) {
                throw ValidationException::withMessages(['progression' => 'Data batch tidak valid.']);
            }
            $request->replace($decoded);
        }
    }
}
