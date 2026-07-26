<?php

namespace App\Actions;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CreateChapaCheckout
{
    public function __construct(private PaymentGateway $gateway) {}

    public function handle(User $buyer): Order
    {
        $order = DB::transaction(function () use ($buyer): Order {
            $cartItems = CartItem::query()
                ->whereBelongsTo($buyer)
                ->with(['product.author', 'product.authors', 'productPlan'])
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                throw new RuntimeException('Your cart is empty.');
            }

            foreach ($cartItems as $cartItem) {
                if (
                    $cartItem->quantity !== 1
                    || $cartItem->product->status !== ProductStatus::Published
                    || ! $cartItem->productPlan
                    || ! $cartItem->productPlan->is_active
                    || $cartItem->productPlan->product_id !== $cartItem->product_id
                ) {
                    throw new RuntimeException('One or more cart items are no longer available.');
                }
            }

            $subtotalMinor = $cartItems->sum(
                fn (CartItem $item): int => $item->productPlan->price_minor,
            );
            $reference = 'MH-'.Str::upper(Str::ulid()->toBase32());
            $order = Order::create([
                'public_id' => (string) Str::uuid(),
                'transaction_reference' => $reference,
                'buyer_email' => $buyer->email,
                'buyer_user_id' => $buyer->id,
                'amount' => Money::toMajor($subtotalMinor),
                'subtotal_minor' => $subtotalMinor,
                'discount_minor' => 0,
                'total_minor' => $subtotalMinor,
                'currency' => 'ETB',
                'status' => OrderStatus::AwaitingPayment,
            ]);

            foreach ($cartItems as $cartItem) {
                $plan = $cartItem->productPlan;
                $product = $cartItem->product;
                $primaryAttribution = $product->authors->first(
                    fn ($author): bool => (bool) $author->pivot->is_primary,
                );
                $commissionBasisPoints = $primaryAttribution
                    ? (int) $primaryAttribution->pivot->revenue_share_basis_points
                    : 7000;
                $authorShareMinor = intdiv($plan->price_minor * $commissionBasisPoints, 10000);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_plan_id' => $plan->id,
                    'renewal_subscription_id' => $cartItem->renewal_subscription_id,
                    'quantity' => 1,
                    'unit_amount' => Money::toMajor($plan->price_minor),
                    'total' => Money::toMajor($plan->price_minor),
                    'product_name' => $product->name,
                    'plan_name' => $plan->name,
                    'unit_amount_minor' => $plan->price_minor,
                    'discount_minor' => 0,
                    'total_minor' => $plan->price_minor,
                    'currency' => 'ETB',
                    'primary_author_snapshot' => [
                        'id' => $product->author->id,
                        'name' => $product->author->name,
                        'slug' => $product->author->slug,
                    ],
                    'commission_basis_points' => $commissionBasisPoints,
                    'platform_share_minor' => $plan->price_minor - $authorShareMinor,
                    'author_share_minor' => $authorShareMinor,
                    'billing_model' => $plan->billing_model->value,
                    'fulfillment_type' => $plan->fulfillment_type->value,
                    'license_configuration' => [
                        'type' => $plan->license_type,
                        'duration_days' => $plan->license_duration_days,
                        'activation_limit' => $plan->activation_limit,
                        'entitlements' => $plan->entitlements,
                        'keygen_policy_id' => $plan->keygen_policy_id,
                    ],
                    'support_duration_days' => $plan->support_duration_days,
                    'update_duration_days' => $plan->update_duration_days,
                ]);
            }

            $order->payments()->create([
                'provider' => 'chapa',
                'provider_reference' => $reference,
                'amount_minor' => $subtotalMinor,
                'currency' => 'ETB',
                'status' => PaymentStatus::Pending,
            ]);

            return $order;
        });

        $session = $this->gateway->initializeCheckout($order->load('buyer'));
        $order->update(['payment_url' => $session->checkoutUrl]);
        $buyer->cartItems()->delete();

        return $order->refresh();
    }
}
