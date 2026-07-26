<?php

namespace App\Http\Controllers;

use App\Actions\ImportWooCommerceProduct;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WooCommerceWebhookController extends Controller
{
    public function __invoke(Request $request, ImportWooCommerceProduct $importProduct): JsonResponse
    {
        $this->verifySignature($request);

        $payload = $request->json()->all();
        $eventType = $request->header('X-WC-Webhook-Topic', 'order.updated');
        $eventId = hash('sha256', $eventType.'|'.$request->getContent());

        $event = WebhookEvent::query()
            ->where('provider', 'woocommerce')
            ->where('event_id', $eventId)
            ->first();

        if ($event?->processed_at) {
            return response()->json(['status' => 'already_processed']);
        }

        $event ??= WebhookEvent::create([
            'provider' => 'woocommerce',
            'event_type' => $eventType,
            'event_id' => $eventId,
            'payload' => $payload,
        ]);
        $event->increment('attempts');

        try {
            if (str_starts_with($eventType, 'product.')) {
                $this->syncProduct($eventType, $payload, $importProduct);
            }

            if (str_starts_with($eventType, 'order.')) {
                DB::transaction(fn () => $this->mirrorOrder($payload));
            }

            $event->update(['processed_at' => now(), 'last_error' => null]);
        } catch (\Throwable $exception) {
            $event->update(['last_error' => str($exception->getMessage())->limit(5000)]);
            throw $exception;
        }

        return response()->json(['status' => 'processed']);
    }

    private function verifySignature(Request $request): void
    {
        $secret = config('services.woocommerce.webhook_secret');

        abort_if(blank($secret), 503, 'WooCommerce webhook secret is not configured.');

        $signature = $request->header('X-WC-Webhook-Signature');
        $expected = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        abort_unless(hash_equals($expected, (string) $signature), 401);
    }

    private function mirrorOrder(array $payload): ?Order
    {
        $lineItems = collect(Arr::get($payload, 'line_items', []));
        $product = Product::where('wc_product_id', Arr::get($lineItems->first(), 'product_id'))->first();

        if (! $product) {
            return null;
        }

        $buyerEmail = Arr::get($payload, 'billing.email');
        $buyer = $buyerEmail ? User::where('email', $buyerEmail)->first() : null;

        $order = Order::updateOrCreate(
            ['wc_order_id' => Arr::get($payload, 'id')],
            [
                'buyer_email' => $buyerEmail,
                'buyer_user_id' => $buyer?->id,
                'product_id' => $product->id,
                'amount' => Arr::get($payload, 'total', $product->price),
                'currency' => Arr::get($payload, 'currency', 'ETB'),
                'status' => $this->orderStatus((string) Arr::get($payload, 'status')),
                'paid_at' => Arr::get($payload, 'date_paid') ? Carbon::parse(Arr::get($payload, 'date_paid')) : null,
            ]
        );

        foreach ($lineItems as $lineItem) {
            $lineProduct = Product::where('wc_product_id', Arr::get($lineItem, 'product_id'))->first();

            if (! $lineProduct) {
                continue;
            }

            $quantity = max(1, (int) Arr::get($lineItem, 'quantity', 1));
            $total = (float) Arr::get($lineItem, 'total', 0);
            $order->items()->updateOrCreate(
                ['wc_order_item_id' => Arr::get($lineItem, 'id')],
                [
                    'product_id' => $lineProduct->id,
                    'quantity' => $quantity,
                    'unit_amount' => $total / $quantity,
                    'total' => $total,
                ],
            );
        }

        return $order;
    }

    private function syncProduct(string $eventType, array $payload, ImportWooCommerceProduct $importProduct): void
    {
        if ($eventType === 'product.deleted') {
            Product::where('wc_product_id', Arr::get($payload, 'id'))
                ->update(['status' => \App\Enums\ProductStatus::Draft]);

            return;
        }

        $importProduct->handle($payload);
    }

    private function orderStatus(string $status): OrderStatus
    {
        return match ($status) {
            'processing', 'completed' => OrderStatus::Paid,
            'refunded' => OrderStatus::Refunded,
            'failed', 'cancelled' => OrderStatus::Failed,
            default => OrderStatus::Pending,
        };
    }
}
