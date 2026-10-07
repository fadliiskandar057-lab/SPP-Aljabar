<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public function __construct(private readonly WebNotificationService $notifications, private readonly WebPushService $push)
    {
    }

    public function record(User $actor, string $action, Model $subject, string $summary, array $metadata = [], ?string $url = null): ActivityLog
    {
        $activity = new ActivityLog([
            'user_id' => $actor->id,
            'action' => $action,
            'summary' => $summary,
            'metadata' => $metadata,
            'ip_address' => request()?->ip(),
        ]);
        $activity->subject()->associate($subject);
        $activity->save();

        $staff = User::whereIn('role', ['admin_tu', 'bendahara', 'kepala_sekolah'])->get();
        $this->notifications->toUsers($staff, 'Aktivitas manajemen', $summary, $url ?: route('activities.index'), 'info');
        $this->push->sendToUsers($staff->load('pushSubscriptions'), 'Aktivitas manajemen', $summary, $url ?: route('activities.index'));

        return $activity;
    }
}
