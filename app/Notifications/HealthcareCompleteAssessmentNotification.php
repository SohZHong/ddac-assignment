<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HealthcareCompleteAssessmentNotification extends Notification
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
        return ['database'];
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
        // Patient who performed the action
        $actor = auth()->user()->name;

        $start = $this->booking->start_time->format('M d, Y H:i');
        $end   = $this->booking->end_time->format('H:i');

        return [
            'booking_id'      => $this->booking->id,
            'patient_name'    => $actor,
            'schedule_id'     => $this->booking->schedule_id,
            'timestamp'       => now(),
            'message'         => "{$actor} has completed your assessment for thenbooking from {$start} - {$end}",
        ];
    }
}
