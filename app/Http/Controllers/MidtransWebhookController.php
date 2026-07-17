<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Services\MidtransService;
use App\Services\WebNotificationService;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, MidtransService $midtrans, WebNotificationService $notifications)
    {
        $payload = $request->all();
        abort_unless($midtrans->verifySignature($payload), 403);

        $payment = Pembayaran::where('midtrans_order_id', $payload['order_id'] ?? null)->firstOrFail();
        $status = $payload['transaction_status'] ?? 'pending';

        if ($payment->status === 'cancelled') {
            return response()->json(['ok' => true]);
        }

        $wasPaid = in_array($payment->status, ['settlement', 'success'], true);

        if (in_array($status, ['capture', 'settlement'], true)) {
            $payment->update([
                'status' => $status === 'settlement' ? 'settlement' : 'success',
                'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                'paid_at' => now(),
            ]);
            $payment->tagihan()->update(['status' => 'lunas']);
            Pembayaran::resolveMultiBillPayment($payment);
            $payment = $payment->fresh()->load('siswa.kelas', 'tagihan');

            if (! $wasPaid) {
                $this->notifyMidtransSuccess($payment, $notifications, $status);
            }
        } elseif (in_array($status, ['deny', 'cancel', 'expire', 'failure'], true)) {
            $payment->update(['status' => $status === 'expire' ? 'expired' : 'failed']);
            $payment->tagihan()->update(['status' => 'gagal']);
            Pembayaran::revertPrecedingBills($payment);
            $payment = $payment->fresh()->load('siswa.kelas', 'tagihan');
            $this->notifyMidtransFailure($payment, $notifications, $status);
        }

        return response()->json(['ok' => true]);
    }

    private function notifyMidtransSuccess(Pembayaran $payment, WebNotificationService $notifications, string $status): void
    {
        $message = "{$payment->siswa->nama} baru saja membayar menggunakan Midtrans. "
            ."Kelas {$payment->siswa->kelas->nama_kelas}, tagihan {$payment->tagihan->bulan} {$payment->tagihan->tahun}, "
            .'nominal Rp '.number_format($payment->nominal, 0, ',', '.')
            .", invoice {$payment->kode_invoice}, status {$status}"
            .($payment->midtrans_transaction_id ? ", trx {$payment->midtrans_transaction_id}" : '').'.';

        $notifications->toRole('admin_tu', 'Pembayaran Midtrans berhasil', $message, route('admin.payments'), 'success');

        if ($payment->siswa->kelas_id) {
            $notifications->toClassGuardians(
                $payment->siswa->kelas_id,
                'Pembayaran Midtrans siswa berhasil',
                "{$payment->siswa->nama} membayar {$payment->tagihan->bulan} via Midtrans sebesar Rp ".number_format($payment->nominal, 0, ',', '.').'.',
                route('wali.payments'),
                'success',
            );
        }
    }

    private function notifyMidtransFailure(Pembayaran $payment, WebNotificationService $notifications, string $status): void
    {
        $message = "Transaksi Midtrans {$payment->siswa->nama} gagal/berakhir. "
            ."Kelas {$payment->siswa->kelas->nama_kelas}, tagihan {$payment->tagihan->bulan} {$payment->tagihan->tahun}, "
            .'nominal Rp '.number_format($payment->nominal, 0, ',', '.')
            .", invoice {$payment->kode_invoice}, status {$status}.";

        $notifications->toStudent(
            $payment->siswa_id,
            'Pembayaran Midtrans belum berhasil',
            "Pembayaran {$payment->tagihan->bulan} via Midtrans berstatus {$status}. Silakan coba lagi.",
            route('siswa.tagihan'),
            'danger',
        );
        $notifications->toRole('admin_tu', 'Pembayaran Midtrans gagal', $message, route('admin.payments'), 'danger');

        if ($payment->siswa->kelas_id) {
            $notifications->toClassGuardians(
                $payment->siswa->kelas_id,
                'Pembayaran Midtrans siswa gagal',
                "{$payment->siswa->nama} belum berhasil membayar {$payment->tagihan->bulan} via Midtrans.",
                route('wali.arrears'),
                'danger',
            );
        }
    }
}
