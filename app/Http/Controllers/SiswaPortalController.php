<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\MidtransPendingPaymentCleaner;
use App\Services\MidtransPaymentProcessor;
use App\Services\MidtransService;
use App\Services\WebNotificationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SiswaPortalController extends Controller
{
    public function prepareFuturePayment(Request $request, \App\Services\FuturePaymentService $future)
    {
        $data = $request->validate(['until' => ['required', 'date_format:Y-m']]);
        $student = auth()->user()->siswa;
        abort_unless($student, 403);
        $plan = $future->quote($student, $data['until']);
        return view('siswa.future_payment_methods', compact('plan'));
    }

    public function startFuturePayment(Request $request, \App\Services\FuturePaymentService $future, MidtransService $midtrans, WebNotificationService $notifications)
    {
        $data = $request->validate([
            'until' => ['required', 'date_format:Y-m'],
            'method' => ['required', 'in:tunai,midtrans'],
        ]);
        $student = auth()->user()->siswa;
        abort_unless($student && $student->status === 'aktif', 403);
        try {
            [$payment, $snapToken] = \Illuminate\Support\Facades\DB::transaction(function () use ($student, $data, $future, $midtrans) {
                $student->newQuery()->whereKey($student->id)->lockForUpdate()->firstOrFail();
                $plan = $future->quote($student, $data['until']);
                $payment = Pembayaran::create([
                    'siswa_id' => $student->id, 'tagihan_id' => null,
                    'kode_invoice' => 'INV-FUT-'.Str::upper(Str::random(16)),
                    'metode' => $data['method'], 'nominal' => $plan['total'], 'status' => 'pending',
                    'midtrans_order_id' => $data['method'] === 'midtrans' ? 'SPP-FUT-'.Str::upper(Str::random(16)) : null,
                    'future_plan' => $plan,
                ]);
                $ids = collect($plan['items'])->pluck('id')->filter();
                Tagihan::where('siswa_id', $student->id)->whereIn('id', $ids)->update(['status' => 'menunggu_konfirmasi']);
                $snapToken = $data['method'] === 'midtrans' ? $midtrans->createSnapToken($payment->load('siswa', 'tagihan')) : null;
                return [$payment, $snapToken];
            });
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            return redirect()->route('siswa.tagihan')->withInput($data)->withErrors(['until' => 'Pembayaran belum dapat dibuat. Silakan coba lagi. Tidak ada tagihan masa depan yang disimpan.']);
        }
        if ($payment->metode === 'tunai') {
            $notifications->toRole('bendahara', 'Invoice tunai masa depan',
                $student->nama.' mengajukan pembayaran sampai '.$data['until'].'. Invoice '.$payment->kode_invoice,
                route('treasury.cash.queue'), 'warning');
            return redirect()->route('invoice.show', $payment)->with('success', 'Invoice tunai dibuat. Bawa ke TU untuk konfirmasi. Tagihan masa depan dicatat setelah pembayaran dikonfirmasi.');
        }
        return view('siswa.midtrans', compact('payment', 'snapToken'));
    }

    public function bills(MidtransPendingPaymentCleaner $midtransCleaner)
    {
        $midtransCleaner->deleteExpired();

        $tagihan = Tagihan::with(['pembayaran' => fn ($q) => $q->latest()])
            ->where('siswa_id', auth()->user()->siswa_id)
            ->latest()
            ->paginate(12);
        $coverage = $this->sppCoverage();
        return view('siswa.tagihan', compact('tagihan', 'coverage'));
    }

    public function history(MidtransPendingPaymentCleaner $midtransCleaner)
    {
        $midtransCleaner->deleteExpired();

        $payments = Pembayaran::with('tagihan')
            ->where('siswa_id', auth()->user()->siswa_id)
            ->where('status', '!=', 'cancelled')
            ->latest()
            ->paginate(12);
        $coverage = $this->sppCoverage();
        return view('siswa.riwayat', compact('payments', 'coverage'));
    }

    public function profile(MidtransPendingPaymentCleaner $midtransCleaner)
    {
        $midtransCleaner->deleteExpired();

        $siswa = auth()->user()->siswa->load('kelas', 'tahunAjaran', 'progressions.targetYear');
        $tagihan = Tagihan::where('siswa_id', $siswa->id)->latest()->get();
        $payments = Pembayaran::with('tagihan')
            ->where('siswa_id', $siswa->id)
            ->where('status', '!=', 'cancelled')
            ->latest()
            ->limit(5)
            ->get();

        return view('siswa.profil', [
            'siswa' => $siswa,
            'coverage' => app(\App\Services\SppCoverageSummary::class)->summarize($tagihan),
            'summary' => [
                'total_tagihan' => $tagihan->count(),
                'lunas' => $tagihan->where('status', 'lunas')->count(),
                'belum_lunas' => $tagihan->where('status', 'belum_lunas')->count(),
                'tunggakan' => $tagihan->whereNotIn('status', ['lunas', 'gratis'])->sum('nominal'),
            ],
            'payments' => $payments,
        ]);
    }

    private function sppCoverage(): array
    {
        return app(\App\Services\SppCoverageSummary::class)->summarize(
            Tagihan::where('siswa_id', auth()->user()->siswa_id)->get()
        );
    }

    public function cash(Tagihan $tagihan, WebNotificationService $notifications, MidtransPendingPaymentCleaner $midtransCleaner)
    {
        abort_unless($tagihan->siswa_id === auth()->user()->siswa_id, 403);
        $midtransCleaner->deleteExpired();
        $tagihan->refresh();

        if (!in_array($tagihan->status, ['belum_lunas', 'gagal'], true)) {
            return back()->withErrors(['tagihan' => 'Tagihan ini sedang diproses atau sudah lunas.']);
        }

        Pembayaran::where('tagihan_id', $tagihan->id)
            ->where('metode', 'midtrans')
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        $payment = Pembayaran::firstOrCreate(
            ['tagihan_id' => $tagihan->id, 'metode' => 'tunai', 'status' => 'pending'],
            [
                'siswa_id' => $tagihan->siswa_id,
                'kode_invoice' => 'INV-CASH-'.now()->format('YmdHis').'-'.$tagihan->id,
                'nominal' => $tagihan->nominal,
            ]
        );
        $tagihan->update(['status' => 'menunggu_konfirmasi']);

        if (! $payment->wasRecentlyCreated) {
            return redirect()->route('invoice.show', $payment);
        }

        $payment->load('siswa.kelas', 'tagihan');
        $notifications->toRole(
            'bendahara',
            'Invoice tunai baru',
            "{$payment->siswa->nama} membuat invoice tunai {$payment->kode_invoice}.",
            route('treasury.cash.queue'),
            'warning',
        );
        if ($payment->siswa->kelas_id) {
            $notifications->toClassGuardians(
                $payment->siswa->kelas_id,
                'Siswa membuat invoice tunai',
                "{$payment->siswa->nama} menunggu verifikasi pembayaran {$payment->tagihan->bulan}.",
                route('wali.payments'),
                'warning',
            );
        }

        return redirect()->route('invoice.show', $payment)->with('success', 'Invoice tunai dibuat. Silakan bawa ke TU untuk verifikasi.');
    }

    public function payOnline(Tagihan $tagihan, MidtransService $midtrans, WebNotificationService $notifications, MidtransPendingPaymentCleaner $midtransCleaner)
    {
        abort_unless($tagihan->siswa_id === auth()->user()->siswa_id, 403);
        $midtransCleaner->deleteExpired();
        $tagihan->refresh();

        if (!in_array($tagihan->status, ['belum_lunas', 'gagal'], true)) {
            return back()->withErrors(['tagihan' => 'Tagihan ini sedang diproses atau sudah lunas.']);
        }

        Pembayaran::where('tagihan_id', $tagihan->id)
            ->where('metode', 'midtrans')
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        $payment = Pembayaran::create([
            'tagihan_id' => $tagihan->id,
            'siswa_id' => $tagihan->siswa_id,
            'kode_invoice' => 'INV-MID-'.now()->format('YmdHis').'-'.$tagihan->id,
            'metode' => 'midtrans',
            'nominal' => $tagihan->nominal,
            'status' => 'pending',
            'midtrans_order_id' => 'SPP-'.Str::upper(Str::random(8)).'-'.$tagihan->id,
        ]);

        try {
            $snapToken = $midtrans->createSnapToken($payment->load('siswa', 'tagihan'));
            $tagihan->update(['status' => 'menunggu_konfirmasi']);
            $notifications->toUser(
                auth()->user(),
                'Transaksi online dibuat',
                "Invoice {$payment->kode_invoice} siap dibayar melalui Midtrans.",
                route('siswa.riwayat'),
                'info',
            );
        } catch (Exception $exception) {
            $payment->update(['status' => 'failed']);

            return back()->withErrors([
                'midtrans' => 'Gagal membuat transaksi Midtrans. Periksa MIDTRANS_SERVER_KEY dan MIDTRANS_CLIENT_KEY di file .env. Detail: '.$exception->getMessage(),
            ]);
        }

        return view('siswa.midtrans', compact('payment', 'snapToken'));
    }

    public function finishMidtrans(Request $request, Pembayaran $pembayaran, MidtransService $midtrans, MidtransPaymentProcessor $processor, WebNotificationService $notifications)
    {
        abort_unless($pembayaran->siswa_id === auth()->user()->siswa_id, 403);
        abort_unless($pembayaran->metode === 'midtrans', 422);

        try {
            // Never trust browser callback fields as proof of payment.
            $status = $processor->process($pembayaran, $midtrans->getTransactionStatus($pembayaran), $notifications);
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['ok' => false, 'message' => 'Status pembayaran belum dapat diverifikasi. Coba buka riwayat beberapa saat lagi.'], 422);
        }

        return response()->json([
            'ok' => true,
            'status' => $status,
            'paid' => in_array($status, ['settlement', 'success'], true),
            'redirect' => route('siswa.riwayat'),
        ]);
    }

    public function cancelPendingPayment(Pembayaran $pembayaran)
    {
        abort_unless($pembayaran->siswa_id === auth()->user()->siswa_id, 403);

        if ($pembayaran->future_plan) {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($pembayaran) {
                $payment = Pembayaran::lockForUpdate()->findOrFail($pembayaran->id);
                if ($payment->status !== 'pending') {
                    return back()->withErrors(['pembayaran' => 'Hanya pembayaran pending yang bisa dibatalkan.']);
                }
                $payment->update(['status' => 'cancelled']);
                Pembayaran::revertPrecedingBills($payment);
                return redirect()->route('siswa.tagihan')->with('success', 'Pembayaran masa depan dibatalkan. Tidak ada tagihan masa depan yang dibuat.');
            });
        }

        if ($pembayaran->status !== 'pending') {
            return back()->withErrors(['pembayaran' => 'Hanya pembayaran yang masih pending yang bisa dibatalkan.']);
        }

        $pembayaran->update(['status' => 'cancelled']);

        $hasOtherActivePayment = Pembayaran::where('tagihan_id', $pembayaran->tagihan_id)
            ->where('id', '!=', $pembayaran->id)
            ->where(function ($query) {
                $query
                    ->whereIn('status', ['settlement', 'success'])
                    ->orWhere('status', 'pending');
            })
            ->exists();

        if (! $hasOtherActivePayment) {
            $pembayaran->tagihan()->update(['status' => 'belum_lunas']);
        }
        Pembayaran::revertPrecedingBills($pembayaran);

        return redirect()->route('siswa.tagihan')->with('success', 'Pembayaran pending dibatalkan. Silakan pilih metode pembayaran lagi.');
    }
}
