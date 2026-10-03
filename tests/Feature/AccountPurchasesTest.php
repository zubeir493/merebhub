<?php

use App\Domain\Fulfillment\Enums\FulfillmentUnitStatus;
use App\Models\Credential;
use App\Models\Entitlement;
use App\Models\FulfillmentUnit;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Lunar\Core\Models\Order;
use Lunar\Core\Models\OrderLine;
use Lunar\Core\Models\ProductOption;
use Lunar\Core\Models\ProductOptionValue;
use Lunar\Core\Models\ProductVariant;

function createAccountPurchase(User $user, string $externalId): Entitlement
{
    $product = Product::factory()->create(['name' => ['en' => 'Usage Product']]);
    $order = Order::factory()->placed()->create(['user_id' => $user->getKey()]);
    $orderLine = OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'type' => 'digital',
        'requires_shipping' => false,
        'requires_fulfilment' => false,
    ]);
    $unit = FulfillmentUnit::query()->create([
        'order_id' => $order->getKey(),
        'order_line_id' => $orderLine->getKey(),
        'product_id' => $product->getKey(),
        'type' => 'license',
        'provider' => 'keygen',
        'status' => FulfillmentUnitStatus::Completed,
        'idempotency_key' => 'test:account-usage:'.Str::uuid(),
    ]);
    $entitlement = Entitlement::query()->create([
        'user_id' => $user->getKey(),
        'order_id' => $order->getKey(),
        'order_line_id' => $orderLine->getKey(),
        'product_id' => $product->getKey(),
        'fulfillment_unit_id' => $unit->getKey(),
        'type' => 'license',
        'status' => 'active',
        'provider' => 'keygen',
        'external_id' => $externalId,
    ]);
    Credential::query()->create([
        'entitlement_id' => $entitlement->getKey(),
        'type' => 'license_key',
        'secret' => 'MH-ACCOUNT-USAGE-KEY',
    ]);

    return $entitlement;
}

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

test('customers see live Keygen device usage and the license policy limit', function (): void {
    config()->set('marketplace.fulfillment.license_provider', 'keygen');
    config()->set('services.keygen.url', 'https://keygen.localhost:8443');
    config()->set('services.keygen.account_id', 'account-123');
    config()->set('services.keygen.api_token', 'server-token');
    config()->set('services.keygen.verify', false);
    Http::preventStrayRequests();
    $customer = User::factory()->create();
    $purchase = createAccountPurchase($customer, 'license-usage');

    Http::fake([
        'https://keygen.localhost:8443/v1/accounts/account-123/licenses/license-usage' => Http::response([
            'data' => [
                'id' => 'license-usage',
                'attributes' => ['maxMachines' => 5],
                'relationships' => ['machines' => ['meta' => ['count' => 2]]],
            ],
        ]),
    ]);

    $this->actingAs($customer)
        ->get(route('account.purchases'))
        ->assertSuccessful()
        ->assertSee('2 / 5')
        ->assertDontSee('0 / 3');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://keygen.localhost:8443/v1/accounts/account-123/licenses/license-usage');
    expect($purchase->fresh()->external_id)->toBe('license-usage');
});

test('customers see a dash when Keygen device usage is unavailable', function (): void {
    config()->set('marketplace.fulfillment.license_provider', 'keygen');
    config()->set('services.keygen.url', 'https://keygen.localhost:8443');
    config()->set('services.keygen.account_id', 'account-123');
    config()->set('services.keygen.api_token', 'server-token');
    config()->set('services.keygen.verify', false);
    Http::preventStrayRequests();
    $customer = User::factory()->create();
    createAccountPurchase($customer, 'license-unavailable');

    Http::fake([
        'https://keygen.localhost:8443/v1/accounts/account-123/licenses/license-unavailable' => Http::failedConnection(),
    ]);

    $this->actingAs($customer)
        ->get(route('account.purchases'))
        ->assertSuccessful()
        ->assertSee('—')
        ->assertDontSee('0 / 3');
});
