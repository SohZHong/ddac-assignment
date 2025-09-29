<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Channels\SnsChannel;

class BookingNotification extends Notification
{
    use Queueable;

    public Booking $booking;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', SnsChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id'   => $this->booking->id,
            'patient_name' => $this->booking->patient->name,
            'schedule_id'  => $this->booking->schedule_id,
            'start_time'   => $this->booking->start_time,
            'end_time'     => $this->booking->end_time,
            'message'      => 'New booking request awaiting your approval.',
        ];
    }

    public function toSns(object $notifiable) {
        return [
            'booking_id'   => $this->booking->id,
            'patient_name' => $this->booking->patient->name,
            'schedule_id'  => $this->booking->schedule_id,
            'start_time'   => $this->booking->start_time,
            'end_time'     => $this->booking->end_time,
            'message'      => 'New booking request awaiting your approval.',
        ];
    }
}
