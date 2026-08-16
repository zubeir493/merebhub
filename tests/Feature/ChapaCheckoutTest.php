<?php

use App\Models\Product;

beforeEach(function () {
    $this->seed();
});

test('a Lunar variant can be added to the storefront cart', function () {
    $product = Product::query()->with(['defaultUrl', 'variants'])->firstOrFail();

    $this->post(route('cart.store', $product), [
        'variant_id' => $product->variants->first()->id,
    ])->assertRedirect(route('cart.index'));

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
