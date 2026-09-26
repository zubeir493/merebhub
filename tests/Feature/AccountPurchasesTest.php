<?php

use App\Domain\Fulfillment\Enums\FulfillmentUnitStatus;
use App\Models\Entitlement;
use App\Models\FulfillmentUnit;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;
use Lunar\Core\Models\Order;
use Lunar\Core\Models\OrderLine;
use Lunar\Core\Models\ProductOption;
use Lunar\Core\Models\ProductOptionValue;
use Lunar\Core\Models\ProductVariant;

test('customers can view purchases when a variant display name comes from option values', function (): void {
    $customer = User::factory()->create();
    $product = Product::factory()->create(['name' => ['en' => 'Option Product']]);
    $option = ProductOption::factory()->create(['name' => ['en' => 'Edition']]);
    $value = ProductOptionValue::factory()->for($option, 'option')->create(['name' => ['en' => 'Professional']]);
    $variant = ProductVariant::factory()->for($product)->create(['variant_name' => null]);
    $variant->values()->attach($value);
    $order = Order::factory()->placed()->create(['user_id' => $customer->getKey()]);
    $orderLine = OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'type' => 'digital',
        'requires_shipping' => false,
        'requires_fulfilment' => false,
        'purchasable_type' => $variant->getMorphClass(),
        'purchasable_id' => $variant->getKey(),
    ]);
    $unit = FulfillmentUnit::query()->create([
        'order_id' => $order->getKey(),
        'order_line_id' => $orderLine->getKey(),
        'product_id' => $product->getKey(),
        'product_variant_id' => $variant->getKey(),
        'type' => 'license',
        'provider' => 'fake-keygen',
        'status' => FulfillmentUnitStatus::Completed,
        'idempotency_key' => 'test:purchases:'.Str::uuid(),
    ]);
    Entitlement::query()->create([
        'user_id' => $customer->getKey(),
        'order_id' => $order->getKey(),
        'order_line_id' => $orderLine->getKey(),
        'product_id' => $product->getKey(),
        'fulfillment_unit_id' => $unit->getKey(),
        'type' => 'license',
        'status' => 'active',
        'provider' => 'fake-keygen',
        'external_id' => 'fake-'.Str::random(12),
    ]);
    $this->actingAs($customer)
        ->get(route('account.purchases'))
        ->assertSuccessful()
        ->assertSee('Option Product')
        ->assertSee('Professional');
});
