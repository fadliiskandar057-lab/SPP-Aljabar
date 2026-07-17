<?php

namespace App\Services;

use App\Models\Pembayaran;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
        Config::$curlOptions = [
            CURLOPT_HTTPHEADER => [],
            CURLOPT_CAINFO => base_path('vendor/midtrans/midtrans-php/data/cacert.pem'),
        ];
    }

    public function createSnapToken(Pembayaran $payment): string
    {
        $student = $payment->siswa;

        return Snap::getSnapToken([
            'transaction_details' => [
                'order_id' => $payment->midtrans_order_id,
                'gross_amount' => (int) $payment->nominal,
            ],
            'customer_details' => [
                'first_name' => $student->nama,
                'phone' => $student->no_hp_orang_tua,
            ],
            'item_details' => [[
                'id' => $payment->tagihan_id,
                'price' => (int) $payment->nominal,
                'quantity' => 1,
                'name' => 'SPP '.$payment->tagihan->bulan.' '.$payment->tagihan->tahun,
            ]],
            'expiry' => [
                'start_time' => $payment->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i:s O'),
                'unit' => 'minutes',
                'duration' => max(1, (int) config('services.midtrans.pending_timeout_minutes', 30)),
            ],
        ]);
    }

    public function verifySignature(array $payload): bool
    {
        $signature = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('services.midtrans.server_key'));

        return hash_equals($signature, $payload['signature_key'] ?? '');
    }
}
