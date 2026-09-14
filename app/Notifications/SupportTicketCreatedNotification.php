<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $ticketPublicId,
        public readonly bool $isNew = true,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->isNew ? 'New customer support ticket' : 'Customer replied to a support ticket')
            ->greeting($this->isNew ? 'A new support ticket is waiting' : 'A customer replied')
            ->line('Ticket reference: '.$this->ticketPublicId);

        return $mail->action(
            'Open support inbox',
            route('staff.support.show', ['supportTicket' => $this->ticketPublicId]),
        );
    }
}
