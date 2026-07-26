<?php

namespace App\Http\Controllers;

use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Http\Requests\FulfillmentWebhookRequest;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FulfillmentWebhookController extends Controller
{
    public function __invoke(FulfillmentWebhookRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $event = WebhookEvent::firstOrCreate(
            ['provider' => 'merebhub-plugin', 'event_id' => $payload['event_id']],
            ['event_type' => 'order.fulfilled', 'payload' => $payload],
        );

        if ($event->processed_at) {
            return response()->json(['status' => 'already_processed']);
        }

        DB::transaction(function () use ($event, $payload): void {
            $buyer = User::where('email', $payload['order']['email'])->first();
            $firstProduct = Product::where('wc_product_id', $payload['items'][0]['product_id'])->firstOrFail();
            $order = Order::updateOrCreate(
                ['wc_order_id' => $payload['order']['id']],
                [
                    'buyer_email' => $payload['order']['email'],
                    'buyer_user_id' => $buyer?->id,
                    'product_id' => $firstProduct->id,
                    'amount' => $payload['order']['total'],
                    'currency' => mb_strtoupper($payload['order']['currency']),
                    'status' => OrderStatus::Paid,
                    'paid_at' => filled($payload['order']['paid_at'])
                        ? Carbon::parse($payload['order']['paid_at'])
                        : now(),
                    'fulfillment_error' => null,
                ],
            );

            foreach ($payload['items'] as $payloadItem) {
                $product = Product::where('wc_product_id', $payloadItem['product_id'])->firstOrFail();
                $orderItem = $order->items()->updateOrCreate(
                    ['wc_order_item_id' => $payloadItem['order_item_id']],
                    [
                        'product_id' => $product->id,
                        'quantity' => $payloadItem['quantity'],
                        'unit_amount' => $payloadItem['unit_amount'],
                        'total' => (float) $payloadItem['unit_amount'] * $payloadItem['quantity'],
                    ],
                );
                $license = $payloadItem['license'];

                License::updateOrCreate(
                    ['keygen_license_id' => $license['id']],
                    [
                        'order_id' => $order->id,
                        'order_item_id' => $orderItem->id,
                        'product_id' => $product->id,
                        'buyer_email' => $order->buyer_email,
                        'license_key' => $license['key'],
                        'status' => LicenseStatus::from($license['status']),
                        'activation_limit' => $license['activation_limit'],
                        'expires_at' => $license['expires_at'],
                        'revoked_at' => $license['status'] === LicenseStatus::Revoked->value ? now() : null,
                    ],
                );
            }

            $event->update(['processed_at' => now(), 'attempts' => $event->attempts + 1]);
        });

        return response()->json(['status' => 'processed']);
    }
}
