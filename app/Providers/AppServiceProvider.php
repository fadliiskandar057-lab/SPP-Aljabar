<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $view->with('topbarNotifications', $user
                ? $user->webNotifications()->latest()->limit(8)->get()
                : collect());
            $view->with('unreadNotificationCount', $user
                ? $user->webNotifications()->whereNull('read_at')->count()
                : 0);
        });
    }
}
