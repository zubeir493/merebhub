<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $ticketPublicId) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your MerebHub support ticket has an update')
            ->greeting('A support agent replied')
            ->line('Ticket reference: '.$this->ticketPublicId)
            ->action('View support ticket', route('account.support.show', $this->ticketPublicId));
    }
}
