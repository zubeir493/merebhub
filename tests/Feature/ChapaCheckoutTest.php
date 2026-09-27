<?php

use App\Models\Product;
use App\Models\User;
use Lunar\Core\Models\Order;
use Lunar\Core\Models\OrderLine;

beforeEach(function () {
    $this->seed();
});

test('a Lunar variant can be added to the storefront cart', function () {
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();

    $this->post(route('cart.store', $product), [
        'variant_id' => $product->variants->first()->id,
    ])->assertRedirect(route('products.show', $product));

    $this->get(route('cart.index'))
        ->assertSuccessful()
        ->assertSee($product->name);

    $this->assertDatabaseHas('lunar_cart_lines', [
        'purchasable_id' => $product->variants->first()->id,
        'quantity' => 1,
    ]);
});

test('the former Chapa webhook is no longer exposed', function () {
    $this->post('/webhooks/chapa')->assertNotFound();
});

test('checkout complete page renders without lazy loading violations', function () {
    $user = User::factory()->create();
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();
    $variant = $product->variants->first();

    $order = Order::factory()->placed()->create([
        'user_id' => $user->getKey(),
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'purchasable_type' => $variant->getMorphClass(),
        'purchasable_id' => $variant->getKey(),
        'description' => $product->name,
        'quantity' => 1,
        'sub_total' => 10000,
        'total' => 10000,
    ]);

    $this->actingAs($user)
        ->get(route('checkout.complete', $order))
        ->assertSuccessful()
        ->assertSee('Your software is ready.')
        ->assertSee($order->reference)
        ->assertSee($product->name);
});
