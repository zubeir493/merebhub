<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessageReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $topic,
        public readonly string $subject,
        public readonly string $message,
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
        return (new MailMessage)
            ->subject('MerebHub contact: '.$this->subject)
            ->greeting('New message from '.$this->name)
            ->line('From: '.$this->email)
            ->line('Topic: '.str($this->topic)->headline())
            ->line($this->message);
    }
}
