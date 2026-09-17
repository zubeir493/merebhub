<?php

namespace App\Integrations\Chapa;

use App\Domain\Billing\Actions\EnsureInvoiceSnapshotAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Lunar\Core\Models\Order;
use Throwable;

class VerifyChapaPaymentAction
{
    public function __construct(
        private readonly ChapaClient $client,
        private readonly EnsureInvoiceSnapshotAction $invoiceSnapshots,
    ) {}

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
            $this->invoiceSnapshots->handle($order);

            return $order;
        }

        try {
            $response = $this->client->verify($transactionReference);
            $this->assertSuccessfulResponse($response, $transactionReference, $order);
        } catch (ChapaPaymentVerificationException $exception) {
            if (isset($response) && $this->isExplicitFailure($response)) {
                $this->recordFailedPayment($order, $transactionReference, $response);
            }

            throw $exception;
        } catch (Throwable $exception) {
            throw new ChapaPaymentVerificationException('Chapa payment verification failed.', previous: $exception);
        }

        $verifiedOrder = DB::transaction(function () use ($transactionReference, $order, $response): Order {
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

        $this->invoiceSnapshots->handle($verifiedOrder);

        return $verifiedOrder;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function recordFailedPayment(Order $order, string $transactionReference, array $response): void
    {
        DB::transaction(function () use ($order, $transactionReference, $response): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if (! $lockedOrder->transactions()->where('reference', $transactionReference)->exists()) {
                $lockedOrder->transactions()->create([
                    'success' => false,
                    'driver' => 'chapa',
                    'amount' => $lockedOrder->total,
                    'reference' => $transactionReference,
                    'status' => 'failed',
                    'type' => 'capture',
                    'meta' => [
                        'chapa_status' => data_get($response, 'data.status', data_get($response, 'status')),
                        'provider_reference' => data_get($response, 'data.reference'),
                    ],
                ]);
            }

            $meta = (array) $lockedOrder->meta;
            $meta['chapa'] = [
                ...(array) ($meta['chapa'] ?? []),
                'status' => 'failed',
                'failed_at' => now()->toISOString(),
            ];

            $lockedOrder->forceFill(['meta' => $meta])->save();
        });
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function isExplicitFailure(array $response): bool
    {
        $statuses = [
            strtolower((string) data_get($response, 'status')),
            strtolower((string) data_get($response, 'data.status')),
        ];

        return count(array_intersect($statuses, ['failed', 'cancelled', 'canceled'])) > 0;
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
            || $providerReference !== $transactionReference
            || $providerCurrency !== strtoupper((string) ($orderCurrency?->code ?? 'ETB'))
            || $actualAmount !== $expectedAmount) {
            throw new ChapaPaymentVerificationException('The Chapa payment could not be verified.');
        }
    }
}
