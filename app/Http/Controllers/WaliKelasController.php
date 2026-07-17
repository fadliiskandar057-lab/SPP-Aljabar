<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Services\MidtransPendingPaymentCleaner;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WaliKelasController extends Controller
{
    public function dashboard()
    {
        app(MidtransPendingPaymentCleaner::class)->deleteExpired();

        $kelas = $this->kelas();
        $students = $this->studentsQuery()->get();
        $bills = $this->billsQuery()->get();
        $recentPayments = $this->paymentsQuery()->latest()->limit(8)->get();

        return view('wali.dashboard', [
            'kelas' => $kelas,
            'students' => $students,
            'stats' => $this->summary($students, $bills),
            'recentPayments' => $recentPayments,
        ]);
    }

    public function students(Request $request)
    {
        $students = $this->studentsQuery()
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;
                $query->where(fn ($q) => $q
                    ->where('nama', 'like', "%{$keyword}%")
                    ->orWhere('nis', 'like', "%{$keyword}%")
                    ->orWhere('nama_orang_tua', 'like', "%{$keyword}%")
                    ->orWhere('no_hp_orang_tua', 'like', "%{$keyword}%"));
            })
            ->orderBy('nama')
            ->get();

        return view('wali.students', ['kelas' => $this->kelas(), 'students' => $students]);
    }

    public function bills(Request $request)
    {
        $bills = $this->billsQuery()
            ->when($request->filled('bulan'), fn ($query) => $query->where('bulan', $request->bulan))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;
                $query->whereHas('siswa', fn ($q) => $q
                    ->where('nama', 'like', "%{$keyword}%")
                    ->orWhere('nis', 'like', "%{$keyword}%"));
            })
            ->latest()
            ->get();

        return view('wali.bills', ['kelas' => $this->kelas(), 'bills' => $bills]);
    }

    public function payments(Request $request)
    {
        app(MidtransPendingPaymentCleaner::class)->deleteExpired();

        $payments = $this->paymentsQuery()
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;
                $query->where(fn ($q) => $q
                    ->where('kode_invoice', 'like', "%{$keyword}%")
                    ->orWhereHas('siswa', fn ($student) => $student
                        ->where('nama', 'like', "%{$keyword}%")
                        ->orWhere('nis', 'like', "%{$keyword}%")));
            })
            ->latest()
            ->get();

        return view('wali.payments', ['kelas' => $this->kelas(), 'payments' => $payments]);
    }

    public function arrears()
    {
        $students = $this->studentsQuery()
            ->with(['tagihan' => fn ($query) => $query
                ->whereNotIn('status', ['lunas', 'gratis'])
                ->orderBy('tahun')
                ->orderBy('jatuh_tempo')])
            ->get()
            ->map(function (Siswa $siswa) {
                $siswa->arrears_total = $siswa->tagihan->sum('nominal');
                return $siswa;
            })
            ->filter(fn (Siswa $siswa) => $siswa->tagihan->isNotEmpty())
            ->sortByDesc('arrears_total')
            ->values();

        return view('wali.arrears', ['kelas' => $this->kelas(), 'students' => $students]);
    }

    public function report(Request $request)
    {
        $bills = $this->billsQuery()
            ->when($request->filled('bulan'), fn ($query) => $query->where('bulan', $request->bulan))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->get();

        return view('wali.report', [
            'kelas' => $this->kelas(),
            'bills' => $bills,
            'summary' => [
                'pemasukan' => $bills->where('status', 'lunas')->sum('nominal'),
                'tunggakan' => $bills->whereNotIn('status', ['lunas', 'gratis'])->sum('nominal'),
                'lunas' => $bills->where('status', 'lunas')->count(),
                'belum_lunas' => $bills->whereNotIn('status', ['lunas', 'gratis'])->count(),
                'gratis' => $bills->where('status', 'gratis')->count(),
                'total' => $bills->count(),
            ],
        ]);
    }

    public function contacts()
    {
        return view('wali.contacts', [
            'kelas' => $this->kelas(),
            'students' => $this->studentsQuery()
                ->with(['tagihan' => fn ($query) => $query->whereNotIn('status', ['lunas', 'gratis'])])
                ->orderBy('nama')
                ->get(),
        ]);
    }

    private function kelas(): ?Kelas
    {
        abort_unless(auth()->user()->kelas_id, 403, 'Akun wali kelas belum terhubung ke kelas.');

        return auth()->user()->kelas;
    }

    private function studentsQuery()
    {
        $this->kelas();

        return Siswa::with('kelas')->where('kelas_id', auth()->user()->kelas_id);
    }

    private function billsQuery()
    {
        $this->kelas();

        return Tagihan::with([
            'siswa.kelas',
            'siswa.tagihan' => fn ($query) => $query
                ->whereNotIn('status', ['lunas', 'gratis'])
                ->orderBy('tahun')
                ->orderBy('jatuh_tempo'),
        ])
            ->whereHas('siswa', fn ($query) => $query->where('kelas_id', auth()->user()->kelas_id));
    }

    private function paymentsQuery()
    {
        $this->kelas();

        return Pembayaran::with('siswa.kelas', 'tagihan')
            ->where('status', '!=', 'cancelled')
            ->whereHas('siswa', fn ($query) => $query->where('kelas_id', auth()->user()->kelas_id));
    }

    private function summary(Collection $students, Collection $bills): array
    {
        return [
            'jumlah_siswa' => $students->count(),
            'lunas' => $bills->where('status', 'lunas')->unique('siswa_id')->count(),
            'belum_lunas' => $bills->whereNotIn('status', ['lunas', 'gratis'])->unique('siswa_id')->count(),
            'tunggakan' => $bills->whereNotIn('status', ['lunas', 'gratis'])->sum('nominal'),
            'gratis' => $bills->where('status', 'gratis')->count(),
        ];
    }
}
