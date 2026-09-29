<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Lunar\Core\Models\Order;

class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $orderId) {}

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
        $order = Order::query()
            ->with(['currency', 'lines.purchasable.product'])
            ->find($this->orderId);

        if ($order === null) {
            return (new MailMessage)
                ->subject('Your MerebHub order is confirmed')
                ->greeting('Your purchase is confirmed')
                ->line('Your order is confirmed. Visit your MerebHub account to view your purchases.')
                ->action('View your orders', route('account.orders'));
        }

        $hasLicense = $order->lines->contains(function ($line): bool {
            $product = $line->purchasable?->product;

            if (! $product instanceof Product) {
                return false;
            }

            $summary = json_decode((string) $product->getRawOriginal('fulfillment_summary'), true);

            return is_array($summary) && ($summary['type'] ?? null) === 'license';
        });
        $currency = $order->currency;
        $currencyCode = strtoupper((string) ($order->currency_code ?? $currency?->code ?? 'ETB'));
        $currencyLabel = $currencyCode === 'ETB' ? 'Br' : $currencyCode;
        $total = number_format(
            (int) $order->total / max(1, (int) ($currency?->factor ?? 100)),
            max(0, (int) ($currency?->decimal_places ?? 2)),
            '.',
            ',',
        );

        $mail = (new MailMessage)
            ->subject($hasLicense ? 'Order confirmed — license included' : 'Your MerebHub order is confirmed')
            ->greeting('Thanks for your order')
            ->line('Your order '.$order->reference.' is confirmed.')
            ->line('Order total: '.$total.' '.$currencyLabel)
            ->line('Items in your order:');

        foreach ($order->lines as $line) {
            if ($line->type === 'shipping') {
                continue;
            }

            $mail->line('• '.$line->description.' × '.$line->quantity);
        }

        if ($hasLicense) {
            $mail->line('Your order includes licensed software. We’ll email you again as each license is ready. You can also view your licenses in your MerebHub account.')
                ->action('View your licenses', route('account.purchases'));
        } else {
            $mail->line('Thanks for choosing MerebHub. You can view your order details in your account.')
                ->action('View your orders', route('account.orders'));
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, int>
     */
    public function toArray(object $notifiable): array
    {
        return ['order_id' => $this->orderId];
    }
}
