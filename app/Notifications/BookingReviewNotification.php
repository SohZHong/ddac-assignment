<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Booking;

class BookingReviewNotification extends Notification
{
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
        // Determine status text
        $statusText = $this->booking->status === Booking::CONFIRMED
            ? 'confirmed'
            : 'cancelled';

        // Healthcare professional who performed the action
        $actor = auth()->user() ? auth()->user()->name : 'System';

        return [
            'booking_id'      => $this->booking->id,
            'healthcare_name' => $actor,
            'schedule_id'     => $this->booking->schedule_id,
            'timestamp'       => now(),
            'message'         => "Your booking has been {$statusText} on " 
                                . now()->format('M d, Y H:i') 
                                . " by {$actor}.",
        ];
    }
}
