<?php

namespace App\Http\Middleware;

use App\Services\AutomaticBillingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMonthlyBills
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->user() && $request->route()?->getName()) {
            try {
                app(AutomaticBillingService::class)->sync();
            } catch (\Throwable $exception) {
                report($exception);
                $request->session()->flash('warning', 'Tagihan bulanan belum dapat disinkronkan. Silakan coba lagi beberapa saat atau hubungi TU.');
            }
        }

        return $next($request);
    }
}
