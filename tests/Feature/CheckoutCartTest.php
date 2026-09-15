<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Lunar\Core\Models\Country;

beforeEach(function () {
    $this->seed();
});

test('checkout shows localized cart item names and creates the order', function () {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();

    $this->actingAs($user)
        ->post(route('cart.store', $product), [
            'variant_id' => $product->variants->first()->id,
        ])
        ->assertRedirect(route('products.show', $product));

    $this->get(route('checkout.show'))
        ->assertSuccessful()
        ->assertSee($product->name)
        ->assertDontSee('{"en":');

    Http::fake([
        'https://api.chapa.co/v1/transaction/initialize' => Http::response([
            'status' => 'success',
            'data' => ['checkout_url' => 'https://checkout.chapa.co/test-payment'],
        ]),
    ]);

    $this->post(route('checkout.store'), [
        'first_name' => 'Demo',
        'last_name' => 'Buyer',
        'contact_email' => $user->email,
        'line_one' => 'Bole Road',
        'city' => 'Addis Ababa',
        'postcode' => '1000',
        'country_id' => Country::query()->where('iso3', 'ETH')->firstOrFail()->id,
        'payment_method' => 'chapa',
    ])->assertRedirect();

    $this->assertDatabaseCount('lunar_orders', 1);
});

test('checkout requires an explicit payment method before creating an order', function (): void {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();

    $this->actingAs($user)->postJson(route('cart.store', $product), [
        'variant_id' => $product->variants->first()->id,
    ])->assertSuccessful();

    $response = $this->actingAs($user)->post(route('checkout.store'), [
        'first_name' => 'Demo',
        'last_name' => 'Buyer',
        'contact_email' => $user->email,
        'line_one' => 'Bole Road',
        'city' => 'Addis Ababa',
        'postcode' => '1000',
        'country_id' => Country::query()->where('iso3', 'ETH')->firstOrFail()->id,
    ]);

    $response->assertSessionHasErrors('payment_method');
    $this->assertDatabaseCount('lunar_orders', 0);
});

test('adding a product to the cart returns JSON without redirecting', function (): void {
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();

    $this->postJson(route('cart.store', $product), [
        'variant_id' => $product->variants->first()->id,
    ])
        ->assertSuccessful()
        ->assertJson([
            'message' => 'Added to your cart.',
            'cart_count' => 1,
        ])
        ->assertHeader('content-type', 'application/json');
});

test('storefront keeps the footer at the bottom of the page layout', function (): void {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('<body class="flex min-h-screen flex-col', false)
        ->assertSee('<main class="flex-1">', false)
        ->assertSee('<footer class="mt-auto', false);
});
