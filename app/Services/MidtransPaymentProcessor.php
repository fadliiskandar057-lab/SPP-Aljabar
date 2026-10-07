<?php

namespace App\Services;

use App\Models\Pembayaran;
use Illuminate\Support\Facades\DB;

class MidtransPaymentProcessor
{
    /** Apply a verified Midtrans status. Returns the stored payment status. */
    public function process(Pembayaran $payment, array $payload, WebNotificationService $notifications): string
    {
        $status = $payload['transaction_status'] ?? 'pending';
        $notify = null;

        $payment = DB::transaction(function () use ($payment, $payload, $status, &$notify) {
            $locked = Pembayaran::with(['siswa.kelas', 'tagihan'])
                ->lockForUpdate()->findOrFail($payment->id);

            if ($locked->status === 'cancelled') {
                return $locked;
            }

            if (in_array($status, ['capture', 'settlement'], true)) {
                $wasPaid = in_array($locked->status, ['settlement', 'success'], true);
                $locked->update([
                    'status' => $status === 'settlement' ? 'settlement' : 'success',
                    'midtrans_transaction_id' => $payload['transaction_id'] ?? $locked->midtrans_transaction_id,
                    'paid_at' => $locked->paid_at ?? now(),
                ]);
                $locked->tagihan()->update(['status' => 'lunas']);
                Pembayaran::where('tagihan_id', $locked->tagihan_id)->where('id', '!=', $locked->id)
                    ->where('status', 'pending')->update(['status' => 'cancelled']);
                Pembayaran::resolveMultiBillPayment($locked);
                $notify = $wasPaid ? null : 'success';
            } elseif (in_array($status, ['deny', 'cancel', 'expire', 'failure'], true)) {
                $wasFailed = in_array($locked->status, ['failed', 'expired'], true);
                $locked->update(['status' => $status === 'expire' ? 'expired' : 'failed']);
                $locked->tagihan()->update(['status' => 'gagal']);
                Pembayaran::revertPrecedingBills($locked);
                $notify = $wasFailed ? null : 'failure';
            }

            return $locked->fresh()->load('siswa.kelas', 'tagihan');
        });

        if ($notify === 'success') {
            $this->notifySuccess($payment, $notifications, $status);
        } elseif ($notify === 'failure') {
            $this->notifyFailure($payment, $notifications, $status);
        }

        return $payment->status;
    }

    private function notifySuccess(Pembayaran $payment, WebNotificationService $notifications, string $status): void
    {
        $student = $payment->siswa;
        $class = $student->kelas?->nama_kelas ?? '-';
        $message = "{$student->nama} membayar melalui Midtrans. Kelas {$class}, tagihan {$payment->tagihan->bulan} {$payment->tagihan->tahun}, nominal Rp "
            .number_format($payment->nominal, 0, ',', '.').", invoice {$payment->kode_invoice}, status {$status}.";
        $notifications->toRole('bendahara', 'Pembayaran Midtrans berhasil', $message, route('treasury.payments'), 'success');
        $notifications->toStudent($payment->siswa_id, 'Pembayaran online berhasil', "Pembayaran {$payment->tagihan->bulan} sudah tercatat lunas.", route('siswa.riwayat'), 'success');
        if ($student->kelas_id) {
            $notifications->toClassGuardians($student->kelas_id, 'Pembayaran Midtrans siswa berhasil', "{$student->nama} membayar {$payment->tagihan->bulan} via Midtrans sebesar Rp ".number_format($payment->nominal, 0, ',', '.').'.', route('wali.payments'), 'success');
        }
    }

    private function notifyFailure(Pembayaran $payment, WebNotificationService $notifications, string $status): void
    {
        $student = $payment->siswa;
        $notifications->toStudent($payment->siswa_id, 'Pembayaran Midtrans belum berhasil', "Pembayaran {$payment->tagihan->bulan} via Midtrans berstatus {$status}. Silakan coba lagi.", route('siswa.tagihan'), 'danger');
        $notifications->toRole('bendahara', 'Pembayaran Midtrans gagal', "Transaksi Midtrans {$student->nama} berstatus {$status}, invoice {$payment->kode_invoice}.", route('treasury.payments'), 'danger');
        if ($student->kelas_id) {
            $notifications->toClassGuardians($student->kelas_id, 'Pembayaran Midtrans siswa gagal', "{$student->nama} belum berhasil membayar {$payment->tagihan->bulan} via Midtrans.", route('wali.arrears'), 'danger');
        }
    }
}
