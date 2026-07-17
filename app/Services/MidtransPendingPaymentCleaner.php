<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use Carbon\Carbon;

class MidtransPendingPaymentCleaner
{
    public function deleteExpired(): int
    {
        $expiredPayments = Pembayaran::with('tagihan')
            ->where('metode', 'midtrans')
            ->where('status', 'pending')
            ->where('created_at', '<=', Carbon::now()->subMinutes($this->timeoutMinutes()))
            ->get();

        $deleted = 0;

        foreach ($expiredPayments as $payment) {
            $tagihan = $payment->tagihan;
            Pembayaran::revertPrecedingBills($payment);
            $payment->delete();
            $deleted++;

            if ($tagihan && ! $this->hasActiveOrPaidPayment($tagihan)) {
                $tagihan->update(['status' => 'belum_lunas']);
            }
        }

        return $deleted;
    }

    public function timeoutMinutes(): int
    {
        return max(1, (int) config('services.midtrans.pending_timeout_minutes', 30));
    }

    private function hasActiveOrPaidPayment(Tagihan $tagihan): bool
    {
        return Pembayaran::where('tagihan_id', $tagihan->id)
            ->where(function ($query) {
                $query
                    ->whereIn('status', ['settlement', 'success'])
                    ->orWhere(function ($pending) {
                        $pending
                            ->where('status', 'pending')
                            ->whereIn('metode', ['tunai', 'manual', 'midtrans']);
                    });
            })
            ->exists();
    }
}
