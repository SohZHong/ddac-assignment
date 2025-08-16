<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $status,
        public ?string $reason = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Professional Account Application Update')
            ->greeting('Hello ' . $notifiable->name);

        if ($this->status === 'approved') {
            $message->line('Congratulations! Your professional account application has been approved.')
                   ->line('You can now access all features available to your role.')
                   ->action('Go to Dashboard', url('/dashboard'));
        } else {
            $message->line('Your professional account application has been reviewed and could not be approved at this time.')
                   ->line('Reason for rejection:')
                   ->line($this->reason)
                   ->line('You may submit a new application with updated credentials.')
                   ->action('Update Profile', url('/settings/profile'));
        }

        return $message->line('Thank you for using our platform!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'status' => $this->status,
            'reason' => $this->reason,
        ];
    }
}
