<?php

use App\Actions\ProcessVerifiedPayment;
use App\Enums\BillingInterval;
use App\Enums\BillingModel;
use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Payments\VerifiedPayment;
use Illuminate\Support\Facades\Queue;

test('a verified manual renewal creates a new term and preserves history', function () {
    Queue::fake();
    $buyer = User::factory()->create();
    $product = Product::factory()->published()->create();
    $plan = ProductPlan::factory()->for($product)->create([
        'billing_model' => BillingModel::ManualSubscription,
        'billing_interval' => BillingInterval::Monthly,
        'license_type' => 'fixed_term',
    ]);
    $previousOrder = Order::factory()->for($buyer, 'buyer')->for($product)->paid()->create();
    $previousItem = $previousOrder->items()->create([
        'product_id' => $product->id,
        'product_plan_id' => $plan->id,
        'quantity' => 1,
        'unit_amount' => '100.00',
        'total' => '100.00',
        'product_name' => $product->name,
        'plan_name' => $plan->name,
        'unit_amount_minor' => 10000,
        'total_minor' => 10000,
        'currency' => 'ETB',
    ]);
    $previous = Subscription::create([
        'public_id' => fake()->uuid(),
        'customer_id' => $buyer->id,
        'product_id' => $product->id,
        'product_plan_id' => $plan->id,
        'order_item_id' => $previousItem->id,
        'status' => SubscriptionStatus::Active,
        'starts_at' => now()->subMonth(),
        'expires_at' => now()->addDays(10)->startOfSecond(),
    ]);
    $order = Order::factory()->for($buyer, 'buyer')->for($product)->create([
        'public_id' => fake()->uuid(),
        'transaction_reference' => 'MH-RENEWAL-1',
        'subtotal_minor' => 10000,
        'total_minor' => 10000,
        'status' => OrderStatus::AwaitingPayment,
    ]);
    $item = $order->items()->create([
        'product_id' => $product->id,
        'product_plan_id' => $plan->id,
        'renewal_subscription_id' => $previous->id,
        'quantity' => 1,
        'unit_amount' => '100.00',
        'total' => '100.00',
        'product_name' => $product->name,
        'plan_name' => $plan->name,
        'unit_amount_minor' => 10000,
        'total_minor' => 10000,
        'currency' => 'ETB',
        'billing_model' => BillingModel::ManualSubscription,
    ]);
    $order->payments()->create([
        'provider_reference' => 'MH-RENEWAL-1',
        'amount_minor' => 10000,
    ]);
    $verified = new VerifiedPayment(
        transactionReference: 'MH-RENEWAL-1',
        providerPaymentId: 'AP-RENEWAL',
        amountMinor: 10000,
        currency: 'ETB',
        status: 'success',
        payload: ['data' => ['status' => 'success']],
    );

    expect(app(ProcessVerifiedPayment::class)->handle($order, $verified))->toBeTrue()
        ->and(app(ProcessVerifiedPayment::class)->handle($order->fresh(), $verified))->toBeFalse();

    $renewal = Subscription::where('order_item_id', $item->id)->sole();

    expect($previous->fresh()->status)->toBe(SubscriptionStatus::Renewed)
        ->and($renewal->previous_subscription_id)->toBe($previous->id)
        ->and($renewal->starts_at->equalTo($previous->expires_at))->toBeTrue()
        ->and($renewal->expires_at->equalTo($previous->expires_at->copy()->addMonthNoOverflow()))->toBeTrue();
});

test('customers cannot renew another customers subscription', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $product = Product::factory()->published()->create();
    $plan = ProductPlan::factory()->for($product)->create([
        'billing_model' => BillingModel::ManualSubscription,
        'billing_interval' => BillingInterval::Yearly,
    ]);
    $order = Order::factory()->for($owner, 'buyer')->for($product)->paid()->create();
    $item = $order->items()->create([
        'product_id' => $product->id,
        'product_plan_id' => $plan->id,
        'quantity' => 1,
        'unit_amount' => '100.00',
        'total' => '100.00',
        'product_name' => $product->name,
        'plan_name' => $plan->name,
        'unit_amount_minor' => 10000,
        'total_minor' => 10000,
        'currency' => 'ETB',
    ]);
    $subscription = Subscription::create([
        'public_id' => fake()->uuid(),
        'customer_id' => $owner->id,
        'product_id' => $product->id,
        'product_plan_id' => $plan->id,
        'order_item_id' => $item->id,
        'status' => SubscriptionStatus::Active,
        'starts_at' => now(),
        'expires_at' => now()->addYear(),
    ]);

    $this->actingAs($other)
        ->post(route('account.subscriptions.renew', $subscription))
        ->assertNotFound();
});
