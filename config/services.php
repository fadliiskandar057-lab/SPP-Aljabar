<?php

return [
    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'pending_timeout_minutes' => env('MIDTRANS_PENDING_TIMEOUT_MINUTES', 30),
    ],
    'webpush' => ['public_key' => env('VAPID_PUBLIC_KEY'), 'private_key' => env('VAPID_PRIVATE_KEY'), 'subject' => env('VAPID_SUBJECT')],
    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'sender_email' => env('BREVO_SENDER_EMAIL', env('MAIL_FROM_ADDRESS')),
        'sender_name' => env('BREVO_SENDER_NAME', env('MAIL_FROM_NAME', env('APP_NAME'))),
    ],
];
