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
        $summary = null;

        $payment = DB::transaction(function () use ($payment, $payload, $status, &$notify, &$summary) {
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
                if (! $wasPaid) {
                    $parts = Pembayaran::with('tagihan')->where('siswa_id', $locked->siswa_id)
                        ->where(function ($query) use ($locked) {
                            $query->whereKey($locked->id);
                            if ($locked->midtrans_order_id) {
                                $query->orWhere('midtrans_order_id', 'like', $locked->midtrans_order_id.'-%');
                            }
                        })->whereIn('status', ['success', 'settlement'])->get();
                    $summary = [
                        'total' => (int) $parts->sum('nominal'),
                        'months' => $parts->count(),
                        'periods' => $parts->sortBy(fn ($part) => $part->tagihan->jatuh_tempo->format('Y-m-d'))
                            ->map(fn ($part) => $part->tagihan->bulan.' '.$part->tagihan->tahun)->implode(', '),
                    ];
                    $message = "{$locked->siswa->nama} membayar SPP {$summary['months']} bulan melalui Midtrans, total Rp "
                        .number_format($summary['total'], 0, ',', '.').". Periode: {$summary['periods']}. Invoice {$locked->kode_invoice}.";
                    app(ActivityLogger::class)->record(auth()->user(), 'payment.midtrans_settled', $locked, $message, $summary, null, false);
                }
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
            $this->notifySuccess($payment, $notifications, $summary);
        } elseif ($notify === 'failure') {
            $this->notifyFailure($payment, $notifications, $status);
        }

        return $payment->status;
    }

    private function notifySuccess(Pembayaran $payment, WebNotificationService $notifications, array $summary): void
    {
        $student = $payment->siswa;
        $class = $student->kelas?->nama_kelas ?? '-';
        $message = "{$student->nama}, kelas {$class}, membayar SPP {$summary['months']} bulan melalui Midtrans, total Rp "
            .number_format($summary['total'], 0, ',', '.').". Periode: {$summary['periods']}. Invoice {$payment->kode_invoice}.";
        $staff = \App\Models\User::whereIn('role', ['admin_tu', 'bendahara', 'kepala_sekolah'])->get();
        $notifications->toUsers($staff, 'Pembayaran Midtrans berhasil', $message, route('activities.index'), 'success');
        app(WebPushService::class)->sendToUsers($staff->load('pushSubscriptions'), 'Pembayaran Midtrans berhasil', $message, route('activities.index'));
        $notifications->toStudent($payment->siswa_id, 'Pembayaran online berhasil', $message, route('siswa.riwayat'), 'success');
        if ($student->kelas_id) {
            $notifications->toClassGuardians($student->kelas_id, 'Pembayaran Midtrans siswa berhasil', $message, route('wali.payments'), 'success');
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
