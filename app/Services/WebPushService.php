<?php

namespace App\Services;

use App\Models\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function sendToUsers($users, string $title, string $body, string $url): void
    {
        if (! config('services.webpush.public_key') || ! config('services.webpush.private_key')) return;
        $push = new WebPush(['VAPID' => ['subject' => config('services.webpush.subject'), 'publicKey' => config('services.webpush.public_key'), 'privateKey' => config('services.webpush.private_key')]]);
        foreach ($users as $user) foreach ($user->pushSubscriptions as $subscription) {
            $push->queueNotification(Subscription::create(['endpoint' => $subscription->endpoint, 'publicKey' => $subscription->public_key, 'authToken' => $subscription->auth_token, 'contentEncoding' => $subscription->content_encoding]), json_encode(compact('title', 'body', 'url')));
        }
        foreach ($push->flush() as $report) if (! $report->isSuccess() && $report->isSubscriptionExpired()) $report->getRequest()->getUri();
    }
}
