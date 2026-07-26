<?php

namespace App\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Support\Money;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class ChapaPaymentGateway implements PaymentGateway
{
    public function __construct(private Factory $http) {}

    public function initializeCheckout(Order $order): CheckoutSession
    {
        $secretKey = $this->secretKey();
        $name = Str::of($order->buyer?->name ?? 'MerebHub Customer')->squish();
        $response = $this->http
            ->withToken($secretKey)
            ->acceptJson()
            ->timeout((int) config('services.chapa.timeout', 15))
            ->retry(2, 250)
            ->post($this->endpoint('/transaction/initialize'), [
                'amount' => Money::toMajor($order->total_minor),
                'currency' => 'ETB',
                'email' => $order->buyer_email,
                'first_name' => $name->before(' ')->toString(),
                'last_name' => $name->after(' ')->toString() ?: 'Customer',
                'tx_ref' => $order->transaction_reference,
                'callback_url' => route('payments.chapa.return', $order->public_id),
                'return_url' => route('payments.chapa.return', $order->public_id),
                'customization' => [
                    'title' => 'MerebHub order',
                    'description' => "Order {$order->public_id}",
                ],
            ])
            ->throw();

        $checkoutUrl = $response->json('data.checkout_url');

        if (! is_string($checkoutUrl) || ! Str::startsWith($checkoutUrl, 'https://')) {
            throw new RuntimeException('Chapa did not return a valid checkout URL.');
        }

        return new CheckoutSession($checkoutUrl, (string) $order->transaction_reference);
    }

    public function verifyTransaction(string $reference): VerifiedPayment
    {
        $response = $this->http
            ->withToken($this->secretKey())
            ->acceptJson()
            ->timeout((int) config('services.chapa.timeout', 15))
            ->retry(2, 250)
            ->get($this->endpoint('/transaction/verify/'.urlencode($reference)))
            ->throw();
        $payload = $response->json();
        $data = Arr::get($payload, 'data', []);

        if (! is_array($data)) {
            throw new RuntimeException('Chapa returned an invalid verification response.');
        }

        return new VerifiedPayment(
            transactionReference: (string) Arr::get($data, 'tx_ref', ''),
            providerPaymentId: (string) Arr::get($data, 'reference', ''),
            amountMinor: Money::fromMajor((string) Arr::get($data, 'amount', '')),
            currency: (string) Arr::get($data, 'currency', ''),
            status: (string) Arr::get($data, 'status', ''),
            payload: $payload,
        );
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.chapa.api_url', 'https://api.chapa.co/v1'), '/').$path;
    }

    private function secretKey(): string
    {
        $secretKey = config('services.chapa.secret_key');

        if (! is_string($secretKey) || $secretKey === '') {
            throw new RuntimeException('Chapa is not configured.');
        }

        return $secretKey;
    }
}
