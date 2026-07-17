<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\MidtransPendingPaymentCleaner;
use App\Services\MidtransService;
use App\Services\WebNotificationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SiswaPortalController extends Controller
{
    public function bills(MidtransPendingPaymentCleaner $midtransCleaner)
    {
        $midtransCleaner->deleteExpired();

        $tagihan = Tagihan::with(['pembayaran' => fn ($q) => $q->latest()])
            ->where('siswa_id', auth()->user()->siswa_id)
            ->latest()
            ->paginate(12);
        return view('siswa.tagihan', compact('tagihan'));
    }

    public function history(MidtransPendingPaymentCleaner $midtransCleaner)
    {
        $midtransCleaner->deleteExpired();

        $payments = Pembayaran::with('tagihan')
            ->where('siswa_id', auth()->user()->siswa_id)
            ->where('status', '!=', 'cancelled')
            ->latest()
            ->paginate(12);
        return view('siswa.riwayat', compact('payments'));
    }

    public function profile(MidtransPendingPaymentCleaner $midtransCleaner)
    {
        $midtransCleaner->deleteExpired();

        $siswa = auth()->user()->siswa->load('kelas');
        $tagihan = Tagihan::where('siswa_id', $siswa->id)->latest()->get();
        $payments = Pembayaran::with('tagihan')
            ->where('siswa_id', $siswa->id)
            ->where('status', '!=', 'cancelled')
            ->latest()
            ->limit(5)
            ->get();

        return view('siswa.profil', [
            'siswa' => $siswa,
            'summary' => [
                'total_tagihan' => $tagihan->count(),
                'lunas' => $tagihan->where('status', 'lunas')->count(),
                'belum_lunas' => $tagihan->where('status', 'belum_lunas')->count(),
                'tunggakan' => $tagihan->whereNotIn('status', ['lunas', 'gratis'])->sum('nominal'),
            ],
            'payments' => $payments,
        ]);
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
            'admin_tu',
            'Invoice tunai baru',
            "{$payment->siswa->nama} membuat invoice tunai {$payment->kode_invoice}.",
            route('admin.cash.queue'),
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

    public function finishMidtrans(Request $request, Pembayaran $pembayaran, WebNotificationService $notifications)
    {
        abort_unless($pembayaran->siswa_id === auth()->user()->siswa_id, 403);
        abort_unless($pembayaran->metode === 'midtrans', 422);

        $data = $request->validate([
            'transaction_status' => ['nullable', 'string'],
            'transaction_id' => ['nullable', 'string'],
        ]);

        $status = $data['transaction_status'] ?? 'settlement';

        if (in_array($status, ['capture', 'settlement'], true)) {
            $wasPaid = in_array($pembayaran->status, ['settlement', 'success'], true);
            $pembayaran->update([
                'status' => $status === 'settlement' ? 'settlement' : 'success',
                'midtrans_transaction_id' => $data['transaction_id'] ?? $pembayaran->midtrans_transaction_id,
                'paid_at' => now(),
            ]);
            $pembayaran->tagihan()->update(['status' => 'lunas']);
            Pembayaran::where('tagihan_id', $pembayaran->tagihan_id)
                ->where('id', '!=', $pembayaran->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);
            Pembayaran::resolveMultiBillPayment($pembayaran);
            $pembayaran->load('siswa.kelas', 'tagihan');
            if (! $wasPaid) {
                $notifications->toUser(
                    auth()->user(),
                    'Pembayaran online berhasil',
                    "Pembayaran {$pembayaran->tagihan->bulan} sudah tercatat lunas.",
                    route('siswa.riwayat'),
                    'success',
                );
                $adminMessage = "{$pembayaran->siswa->nama} baru saja membayar menggunakan Midtrans. "
                    ."Kelas {$pembayaran->siswa->kelas->nama_kelas}, tagihan {$pembayaran->tagihan->bulan} {$pembayaran->tagihan->tahun}, "
                    .'nominal Rp '.number_format($pembayaran->nominal, 0, ',', '.')
                    .", invoice {$pembayaran->kode_invoice}, status {$status}"
                    .($pembayaran->midtrans_transaction_id ? ", trx {$pembayaran->midtrans_transaction_id}" : '').'.';
                $notifications->toRole(
                    'admin_tu',
                    'Pembayaran Midtrans berhasil',
                    $adminMessage,
                    route('admin.payments'),
                    'success',
                );
                if ($pembayaran->siswa->kelas_id) {
                    $notifications->toClassGuardians(
                        $pembayaran->siswa->kelas_id,
                        'Pembayaran Midtrans siswa berhasil',
                        "{$pembayaran->siswa->nama} membayar {$pembayaran->tagihan->bulan} via Midtrans sebesar Rp ".number_format($pembayaran->nominal, 0, ',', '.').'.',
                        route('wali.payments'),
                        'success',
                    );
                }
            }

            return response()->json(['ok' => true, 'redirect' => route('siswa.riwayat')]);
        }

        if (in_array($status, ['deny', 'cancel', 'expire', 'failure'], true)) {
            $pembayaran->update(['status' => $status === 'expire' ? 'expired' : 'failed']);
            $pembayaran->tagihan()->update(['status' => 'gagal']);
            Pembayaran::revertPrecedingBills($pembayaran);
            $pembayaran->load('siswa.kelas', 'tagihan');
            $notifications->toUser(
                auth()->user(),
                'Pembayaran Midtrans belum berhasil',
                "Pembayaran {$pembayaran->tagihan->bulan} via Midtrans berstatus {$status}. Silakan coba lagi.",
                route('siswa.tagihan'),
                'danger',
            );
            $adminMessage = "Transaksi Midtrans {$pembayaran->siswa->nama} gagal/berakhir. "
                ."Kelas {$pembayaran->siswa->kelas->nama_kelas}, tagihan {$pembayaran->tagihan->bulan} {$pembayaran->tagihan->tahun}, "
                .'nominal Rp '.number_format($pembayaran->nominal, 0, ',', '.')
                .", invoice {$pembayaran->kode_invoice}, status {$status}.";
            $notifications->toRole('admin_tu', 'Pembayaran Midtrans gagal', $adminMessage, route('admin.payments'), 'danger');
            if ($pembayaran->siswa->kelas_id) {
                $notifications->toClassGuardians(
                    $pembayaran->siswa->kelas_id,
                    'Pembayaran Midtrans siswa gagal',
                    "{$pembayaran->siswa->nama} ({$pembayaran->siswa->kelas->nama_kelas}) belum berhasil membayar {$pembayaran->tagihan->bulan} {$pembayaran->tagihan->tahun} via Midtrans. Nominal Rp ".number_format($pembayaran->nominal, 0, ',', '.').", invoice {$pembayaran->kode_invoice}, status {$status}.",
                    route('wali.arrears'),
                    'danger',
                );
            }
        }

        return response()->json(['ok' => true, 'redirect' => route('siswa.riwayat')]);
    }

    public function cancelPendingPayment(Pembayaran $pembayaran)
    {
        abort_unless($pembayaran->siswa_id === auth()->user()->siswa_id, 403);

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
