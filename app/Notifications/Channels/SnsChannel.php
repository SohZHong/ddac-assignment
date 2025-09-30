<?php

namespace App\Notifications\Channels;

use App\Services\AwsNotificationService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SnsChannel
{
    protected AwsNotificationService $sns;

    public function __construct(AwsNotificationService $sns)
    {
        $this->sns = $sns;
    }

    public function send($notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSns')) {
            return;
        }

        $payload = $notification->toSns($notifiable);

        try {
            $this->sns->publish($payload);
        } catch (\Throwable $e) {
            // log errors — do NOT bubble to user
            Log::error('SNS publish failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }
}