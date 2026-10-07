<?php

use Monolog\Handler\StreamHandler;

return [
    'default' => env('LOG_CHANNEL', 'stack'),
    'channels' => [
        'stack' => ['driver' => 'stack', 'channels' => ['single']],
        'single' => ['driver' => 'single', 'path' => storage_path('logs/laravel.log'), 'level' => env('LOG_LEVEL', 'debug')],
        // Render hanya menampilkan keluaran proses container. Saluran ini membuat
        // exception Laravel tercetak di log deployment/runtime Render.
        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'with' => ['stream' => 'php://stderr'],
            'level' => env('LOG_LEVEL', 'debug'),
        ],
    ],
];
