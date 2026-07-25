<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Jobs\ProvisionOrderLicenseJob;
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
    public function __invoke(Request $request): JsonResponse
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
            if (in_array(Arr::get($payload, 'status'), ['processing', 'completed'], true)) {
                $order = DB::transaction(fn () => $this->mirrorPaidOrder($payload));

                if ($order) {
                    ProvisionOrderLicenseJob::dispatch($order->id);
                }
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

    private function mirrorPaidOrder(array $payload): ?Order
    {
        $lineItem = Arr::first(Arr::get($payload, 'line_items', []));
        $product = Product::where('wc_product_id', Arr::get($lineItem, 'product_id'))->first();

        if (! $product) {
            return null;
        }

        $buyerEmail = Arr::get($payload, 'billing.email');
        $buyer = $buyerEmail ? User::where('email', $buyerEmail)->first() : null;

        return Order::updateOrCreate(
            ['wc_order_id' => Arr::get($payload, 'id')],
            [
                'buyer_email' => $buyerEmail,
                'buyer_user_id' => $buyer?->id,
                'product_id' => $product->id,
                'amount' => Arr::get($payload, 'total', $product->price),
                'currency' => Arr::get($payload, 'currency', 'ETB'),
                'status' => OrderStatus::Paid,
                'paid_at' => Arr::get($payload, 'date_paid') ? Carbon::parse(Arr::get($payload, 'date_paid')) : now(),
            ]
        );
    }
}
