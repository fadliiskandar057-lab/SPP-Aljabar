<?php

use App\Services\MidtransPendingPaymentCleaner;
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();
Schedule::call(fn () => app(MidtransPendingPaymentCleaner::class)->deleteExpired())
    ->name('midtrans-pending-cleaner')
    ->everyMinute()
    ->withoutOverlapping();
Schedule::command('tagihan:generate-bulanan')->dailyAt('01:00')->withoutOverlapping();
