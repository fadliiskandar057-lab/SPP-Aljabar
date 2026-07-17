<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Services\MidtransPendingPaymentCleaner;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        app(MidtransPendingPaymentCleaner::class)->deleteExpired();

        $user = auth()->user();

        if ($user->role === 'siswa') {
            $tagihan = Tagihan::where('siswa_id', $user->siswa_id)->latest()->get();
            $payments = Pembayaran::where('siswa_id', $user->siswa_id)
                ->where('status', '!=', 'cancelled')
                ->latest()
                ->limit(5)
                ->get();
            return view('dashboards.siswa', compact('tagihan', 'payments'));
        }

        if ($user->role === 'wali_kelas') {
            return redirect()->route('wali.dashboard');
        }

        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        $stats = [
            'pemasukan_bulan_ini' => Pembayaran::whereIn('status', ['settlement', 'success'])->whereMonth('paid_at', $month)->whereYear('paid_at', $year)->sum('nominal'),
            'siswa_lunas' => Tagihan::where('status', 'lunas')->whereMonth('updated_at', $month)->distinct('siswa_id')->count('siswa_id'),
            'siswa_belum_lunas' => Siswa::whereHas('tagihan', fn ($q) => $q->where('status', 'belum_lunas'))->count(),
            'menunggu' => Tagihan::where('status', 'menunggu_konfirmasi')->count(),
        ];
        $recentPayments = Pembayaran::with('siswa', 'tagihan')->where('status', '!=', 'cancelled')->latest()->limit(8)->get();
        $monthlyIncome = collect(range(1, 12))->map(fn ($m) => Pembayaran::whereIn('status', ['settlement', 'success'])->whereYear('paid_at', $year)->whereMonth('paid_at', $m)->sum('nominal'));

        return view($user->role === 'kepala_sekolah' ? 'dashboards.kepala' : 'dashboards.admin', compact('stats', 'recentPayments', 'monthlyIncome'));
    }
}
