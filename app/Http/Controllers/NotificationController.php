<?php

namespace App\Http\Controllers;

use App\Models\WebNotification;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()
            ->webNotifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function read(WebNotification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->update(['read_at' => now()]);

        return redirect($notification->url ?: route('notifications.index'));
    }

    public function readAll()
    {
        auth()->user()
            ->webNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
