<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingLinkNotification extends Notification 
{
    use Queueable;

    protected $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Video Consultation Meeting Link')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Dr. ' . $this->data['doctor_name'] . ' has started a video consultation session with you.')
            ->line('Please click the button below to join the video call:')
            ->line('Room ID: ' . $this->data['room_id'])
            ->line('If you have any issues joining the call, please contact our support team.')
            ->salutation('Best regards, Healthcare Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'doctor_name' => $this->data['doctor_name'],
            'room_id' => $this->data['room_id'],
            'timestamp' => now(),
            'message' => "Dr. {$this->data['doctor_name']} has started a video consultation session. Copy the room id {$this->data['room_id']} to join",
        ];
    }
}