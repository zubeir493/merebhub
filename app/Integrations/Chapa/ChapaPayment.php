<?php

namespace App\Integrations\Chapa;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Lunar\Core\DataObjects\PaymentAuthorize;
use Lunar\Core\DataObjects\PaymentCapture;
use Lunar\Core\DataObjects\PaymentRefund;
use Lunar\Core\Models\Transaction;
use Lunar\Core\PaymentTypes\AbstractPayment;
use Throwable;

class ChapaPayment extends AbstractPayment
{
    public function __construct(private readonly ChapaClient $client) {}

    public function authorize(): ?PaymentAuthorize
    {
        $order = $this->order;

        if (! $order && $this->cart) {
            $order = $this->cart->draftOrder()->first() ?: $this->cart->createOrder();
        }

        if (! $order) {
            return new PaymentAuthorize(message: 'The checkout could not be started.');
        }

        try {
            $order->loadMissing(['currency', 'user']);
            $transactionReference = 'mh_'.Str::lower($order->public_id.'_'.Str::random(12));
            $currency = $order->currency;
            $decimalPlaces = $currency?->decimal_places ?? 2;
            $factor = (int) ($currency?->factor ?? 100);
            $email = Str::lower(trim((string) ($order->user?->email ?? $this->data['contact_email'] ?? '')));

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new ChapaException('The account email is invalid.');
            }

            $payload = array_filter([
                'amount' => number_format($order->total / $factor, $decimalPlaces, '.', ''),
                'currency' => strtoupper((string) ($currency?->code ?? 'ETB')),
                'email' => $email,
                'first_name' => $this->data['first_name'] ?? null,
                'last_name' => $this->data['last_name'] ?? null,
                'tx_ref' => $transactionReference,
                'callback_url' => route('payments.chapa.callback'),
                'return_url' => route('payments.chapa.return', ['tx_ref' => $transactionReference]),
                'customization' => [
                    'title' => 'MerebHub',
                    'description' => 'MerebHub marketplace purchase',
                ],
            ], static fn (mixed $value): bool => filled($value));

            $response = $this->client->initialize($payload);

            $checkoutUrl = data_get($response, 'data.checkout_url');

            if (strtolower((string) data_get($response, 'status')) !== 'success' || blank($checkoutUrl)) {
                throw new ChapaException('Chapa did not return a checkout URL.');
            }

            $meta = (array) $order->meta;
            $meta['chapa'] = [
                'tx_ref' => $transactionReference,
                'checkout_url' => $checkoutUrl,
                'status' => 'pending',
            ];
            $order->forceFill(['meta' => $meta])->save();

            return new PaymentAuthorize(
                success: true,
                orderId: $order->id,
                paymentType: 'chapa',
            );
        } catch (Throwable $exception) {
            Log::warning('Chapa payment initialization failed.', [
                'order_id' => $order->getKey(),
                'exception' => $exception::class,
                'message' => Str::limit($exception->getMessage(), 240),
            ]);

            return new PaymentAuthorize(
                message: $exception instanceof ChapaException
                    ? $exception->getMessage()
                    : 'The payment service is temporarily unavailable. Please try again.',
            );
        }
    }

    public function refund(Transaction $transaction, int $amount = 0, $notes = null): PaymentRefund
    {
        return new PaymentRefund(message: 'Chapa refunds must be processed from the Chapa dashboard.');
    }

    public function capture(Transaction $transaction, $amount = 0): PaymentCapture
    {
        return new PaymentCapture(message: 'Chapa redirect payments are captured after verification.');
    }
}
