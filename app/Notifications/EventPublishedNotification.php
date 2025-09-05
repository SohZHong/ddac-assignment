<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EventPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Event $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'event_published',
            'event_id' => $this->event->id,
            'title' => $this->event->title,
            'start_datetime' => optional($this->event->start_datetime)?->format('Y-m-d H:i:s'),
            'end_datetime' => optional($this->event->end_datetime)?->format('Y-m-d H:i:s'),
            'message' => 'A new event has been published.',
        ];
    }
}


