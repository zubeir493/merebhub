<?php

namespace App\Integrations\Chapa;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Lunar\Core\Models\Order;
use Throwable;

class VerifyChapaPaymentAction
{
    public function __construct(private readonly ChapaClient $client) {}

    public function handle(string $transactionReference): Order
    {
        $order = Order::query()
            ->where('meta->chapa->tx_ref', $transactionReference)
            ->with('currency')
            ->first();

        if (! $order) {
            throw (new ModelNotFoundException)->setModel(Order::class, [$transactionReference]);
        }

        if ($order->captures()->where('reference', $transactionReference)->whereSuccess(true)->exists()) {
            return $order;
        }

        try {
            $response = $this->client->verify($transactionReference);
            $this->assertSuccessfulResponse($response, $transactionReference, $order);
        } catch (ChapaPaymentVerificationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ChapaPaymentVerificationException('Chapa payment verification failed.', previous: $exception);
        }

        return DB::transaction(function () use ($transactionReference, $order, $response): Order {
            $lockedOrder = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if (! $lockedOrder->captures()->where('reference', $transactionReference)->whereSuccess(true)->exists()) {
                $lockedOrder->transactions()->create([
                    'success' => true,
                    'driver' => 'chapa',
                    'amount' => $lockedOrder->total,
                    'reference' => $transactionReference,
                    'status' => 'captured',
                    'captured_at' => now(),
                    'type' => 'capture',
                    'meta' => [
                        'chapa_status' => data_get($response, 'data.status'),
                        'provider_reference' => data_get($response, 'data.reference'),
                    ],
                ]);

                $meta = (array) $lockedOrder->meta;
                $meta['chapa'] = [
                    ...(array) ($meta['chapa'] ?? []),
                    'status' => 'paid',
                    'verified_at' => now()->toISOString(),
                ];
                $lockedOrder->forceFill([
                    'meta' => $meta,
                    'placed_at' => $lockedOrder->placed_at ?? now(),
                ])->save();
            }

            return $lockedOrder->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function assertSuccessfulResponse(array $response, string $transactionReference, Order $order): void
    {
        $status = strtolower((string) data_get($response, 'status'));
        $paymentStatus = strtolower((string) data_get($response, 'data.status'));
        $providerReference = (string) (data_get($response, 'data.tx_ref') ?? data_get($response, 'data.trx_ref') ?? '');
        $providerCurrency = strtoupper((string) data_get($response, 'data.currency'));
        $providerAmount = data_get($response, 'data.amount');
        $orderCurrency = $order->currency;
        $decimalPlaces = $orderCurrency?->decimal_places ?? 2;
        $factor = (int) ($orderCurrency?->factor ?? 100);
        $expectedAmount = number_format($order->total / $factor, $decimalPlaces, '.', '');
        $actualAmount = is_numeric($providerAmount)
            ? number_format((float) $providerAmount, $decimalPlaces, '.', '')
            : '';

        if ($status !== 'success'
            || ! in_array($paymentStatus, ['success', 'successful', 'completed', 'paid'], true)
            || ($providerReference !== '' && $providerReference !== $transactionReference)
            || ($providerCurrency !== '' && $providerCurrency !== strtoupper((string) ($orderCurrency?->code ?? 'ETB')))
            || $actualAmount !== $expectedAmount) {
            throw new ChapaPaymentVerificationException('The Chapa payment could not be verified.');
        }
    }
}
