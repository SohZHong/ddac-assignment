<?php

namespace App\Notifications\Channels;

use App\Services\AwsNotificationService;
use Illuminate\Notifications\Notification;

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

        $message = $notification->toSns($notifiable);

        $this->sns->publish($message);
    }
}