<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\WooCommerceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateWooCommerceCheckout
{
    public function __construct(private WooCommerceService $woocommerce) {}

    public function handle(User $buyer): Order
    {
        $cartItems = $buyer->cartItems()
            ->with('product')
            ->get();

        if ($cartItems->isEmpty()) {
            throw new RuntimeException('Your cart is empty.');
        }

        $wooProducts = $this->freshWooProducts($cartItems);
        $lineItems = $cartItems->map(function ($cartItem) use ($wooProducts): array {
            $product = $wooProducts->get($cartItem->product->wc_product_id);

            if (! $product || ($product['status'] ?? null) !== 'publish') {
                throw new RuntimeException("{$cartItem->product->name} is no longer available.");
            }

            $cartItem->product->update([
                'price' => $product['price'],
                'last_synced_at' => now(),
            ]);

            return [
                'product_id' => $cartItem->product->wc_product_id,
                'quantity' => $cartItem->quantity,
            ];
        })->values()->all();

        $wooOrder = $this->woocommerce->createOrder($lineItems, $buyer);

        return DB::transaction(function () use ($buyer, $cartItems, $wooProducts, $wooOrder): Order {
            $order = Order::updateOrCreate(
                ['wc_order_id' => $wooOrder['id']],
                [
                    'buyer_email' => $buyer->email,
                    'buyer_user_id' => $buyer->id,
                    'product_id' => $cartItems->first()->product_id,
                    'amount' => $wooOrder['total'] ?? $cartItems->sum(
                        fn ($item): float => (float) $wooProducts->get($item->product->wc_product_id)['price'] * $item->quantity,
                    ),
                    'currency' => $wooOrder['currency'] ?? 'ETB',
                    'status' => OrderStatus::Pending,
                    'payment_url' => $this->woocommerce->checkoutUrl($wooOrder),
                ],
            );

            foreach ($cartItems as $cartItem) {
                $unitAmount = (string) $wooProducts->get($cartItem->product->wc_product_id)['price'];
                $order->items()->updateOrCreate(
                    ['product_id' => $cartItem->product_id],
                    [
                        'quantity' => $cartItem->quantity,
                        'unit_amount' => $unitAmount,
                        'total' => (float) $unitAmount * $cartItem->quantity,
                    ],
                );
            }

            $buyer->cartItems()->delete();

            return $order->load('items.product');
        });
    }

    private function freshWooProducts(Collection $cartItems): Collection
    {
        $ids = $cartItems->pluck('product.wc_product_id')->filter()->values();

        if ($ids->count() !== $cartItems->count()) {
            throw new RuntimeException('One or more cart items are not connected to WooCommerce.');
        }

        return collect($this->woocommerce->fetchProducts([
            'include' => $ids->implode(','),
            'per_page' => min(100, $ids->count()),
        ]))->keyBy('id');
    }
}
