<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Services\MidtransService;
use App\Services\MidtransPaymentProcessor;
use App\Services\WebNotificationService;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, MidtransService $midtrans, MidtransPaymentProcessor $processor, WebNotificationService $notifications)
    {
        $payload = $request->all();
        abort_unless($midtrans->verifySignature($payload), 403);

        $payment = Pembayaran::where('midtrans_order_id', $payload['order_id'] ?? null)->firstOrFail();
        $processor->process($payment, $payload, $notifications);

        return response()->json(['ok' => true]);
    }

}
