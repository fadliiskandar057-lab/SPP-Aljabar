<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';
    protected $fillable = [
        'tagihan_id', 'siswa_id', 'kode_invoice', 'metode', 'nominal', 'status',
        'midtrans_order_id', 'midtrans_transaction_id', 'paid_at', 'verified_by', 'bukti_path',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public static function resolveMultiBillPayment(self $payment)
    {
        $tagihan = $payment->tagihan;
        if (!$tagihan) return;

        if ($payment->nominal <= $tagihan->nominal) {
            return;
        }

        $monthsMap = [
            'januari' => 1, 'jan' => 1, 'january' => 1,
            'februari' => 2, 'feb' => 2, 'february' => 2,
            'maret' => 3, 'mar' => 3, 'march' => 3,
            'april' => 4, 'apr' => 4,
            'mei' => 5, 'may' => 5,
            'juni' => 6, 'jun' => 6, 'june' => 6,
            'juli' => 7, 'jul' => 7, 'july' => 7,
            'agustus' => 8, 'agu' => 8, 'aug' => 8, 'august' => 8,
            'september' => 9, 'sep' => 9,
            'oktober' => 10, 'okt' => 10, 'oct' => 10, 'october' => 10,
            'november' => 11, 'nov' => 11,
            'desember' => 12, 'des' => 12, 'dec' => 12, 'december' => 12,
        ];

        $monthNumber = function (string $month) use ($monthsMap) {
            $normalized = mb_strtolower($month);
            return $monthsMap[$normalized] ?? (is_numeric($month) ? (int) $month : 1);
        };

        $targetMonthNum = $monthNumber($tagihan->bulan);
        $targetYear = (int) $tagihan->tahun;

        // Get preceding bills that are not lunas/gratis
        $precedingBills = Tagihan::where('siswa_id', $payment->siswa_id)
            ->whereNotIn('status', ['lunas', 'gratis'])
            ->where('id', '!=', $tagihan->id)
            ->get()
            ->filter(function ($bill) use ($monthNumber, $targetMonthNum, $targetYear) {
                $billMonthNum = $monthNumber($bill->bulan);
                $billYear = (int) $bill->tahun;
                
                if ($billYear < $targetYear) {
                    return true;
                }
                if ($billYear === $targetYear && $billMonthNum < $targetMonthNum) {
                    return true;
                }
                return false;
            })
            ->sortBy(function ($bill) use ($monthNumber) {
                return sprintf('%04d%02d', $bill->tahun, $monthNumber($bill->bulan));
            });

        if ($precedingBills->isEmpty()) {
            return;
        }

        foreach ($precedingBills as $bill) {
            self::create([
                'tagihan_id' => $bill->id,
                'siswa_id' => $payment->siswa_id,
                'kode_invoice' => 'INV-MID-ARR-' . now()->format('YmdHis') . '-' . $bill->id,
                'metode' => $payment->metode,
                'nominal' => $bill->nominal,
                'status' => $payment->status,
                'midtrans_order_id' => $payment->midtrans_order_id . '-' . $bill->id,
                'midtrans_transaction_id' => $payment->midtrans_transaction_id,
                'paid_at' => $payment->paid_at ?? now(),
                'verified_by' => $payment->verified_by,
            ]);
            $bill->update(['status' => 'lunas']);
        }

        $payment->update([
            'nominal' => $tagihan->nominal
        ]);
    }

    public static function revertPrecedingBills(self $payment)
    {
        $tagihan = $payment->tagihan;
        if (!$tagihan) return;

        $monthsMap = [
            'januari' => 1, 'jan' => 1, 'january' => 1,
            'februari' => 2, 'feb' => 2, 'february' => 2,
            'maret' => 3, 'mar' => 3, 'march' => 3,
            'april' => 4, 'apr' => 4,
            'mei' => 5, 'may' => 5,
            'juni' => 6, 'jun' => 6, 'june' => 6,
            'juli' => 7, 'jul' => 7, 'july' => 7,
            'agustus' => 8, 'agu' => 8, 'aug' => 8, 'august' => 8,
            'september' => 9, 'sep' => 9,
            'oktober' => 10, 'okt' => 10, 'oct' => 10, 'october' => 10,
            'november' => 11, 'nov' => 11,
            'desember' => 12, 'des' => 12, 'dec' => 12, 'december' => 12,
        ];

        $monthNumber = function (string $month) use ($monthsMap) {
            $normalized = mb_strtolower($month);
            return $monthsMap[$normalized] ?? (is_numeric($month) ? (int) $month : 1);
        };

        $targetMonthNum = $monthNumber($tagihan->bulan);
        $targetYear = (int) $tagihan->tahun;

        $precedingBills = Tagihan::where('siswa_id', $payment->siswa_id)
            ->where('status', 'menunggu_konfirmasi')
            ->where('id', '!=', $tagihan->id)
            ->get()
            ->filter(function ($bill) use ($monthNumber, $targetMonthNum, $targetYear) {
                $billMonthNum = $monthNumber($bill->bulan);
                $billYear = (int) $bill->tahun;
                
                if ($billYear < $targetYear) {
                    return true;
                }
                if ($billYear === $targetYear && $billMonthNum < $targetMonthNum) {
                    return true;
                }
                return false;
            });

        foreach ($precedingBills as $bill) {
            $hasOtherActive = self::where('tagihan_id', $bill->id)
                ->where('id', '!=', $payment->id)
                ->whereIn('status', ['pending', 'success', 'settlement'])
                ->exists();

            if (!$hasOtherActive) {
                $bill->update(['status' => 'belum_lunas']);
            }
        }
    }
}
