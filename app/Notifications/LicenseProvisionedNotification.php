<?php

namespace App\Notifications;

use App\Models\Entitlement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseProvisionedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $entitlementId) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $entitlement = Entitlement::query()
            ->with(['credential', 'product', 'orderLine.purchasable.product', 'fulfillmentUnit'])
            ->find($this->entitlementId);

        if ($entitlement === null || $entitlement->credential === null) {
            if ($entitlement === null) {
                return (new MailMessage)
                    ->subject('Your MerebHub license is being prepared')
                    ->greeting('Your purchase is confirmed')
                    ->line('We are still preparing your license. You can find it in your MerebHub account shortly.')
                    ->action('Open your licenses', route('account.purchases'));
            }

            return (new MailMessage)
                ->subject('Your MerebHub license is being prepared')
                ->greeting('Your purchase is confirmed')
                ->line('We are still preparing your '.$entitlement->product?->name.' — '.$entitlement->variantDisplayName().' license. You can find it in your MerebHub account shortly.')
                ->action('Open your licenses', route('account.purchases'));
        }

        return (new MailMessage)
            ->subject('Your MerebHub license is ready')
            ->greeting('Your license is ready')
            ->line('Your license for '.($entitlement->product?->name ?? 'your purchased product').' — '.$entitlement->variantDisplayName().' is now available.')
            ->line('License key: '.$entitlement->credential->secret)
            ->action('View your licenses', route('account.purchases'))
            ->line('Keep this key private and do not share it publicly.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['entitlement_id' => $this->entitlementId];
    }
}
