<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Lunar\Core\Models\Order;

beforeEach(function () {
    $this->seed();
    config()->set('services.chapa.secret_key', 'test-secret');
});

test('checkout starts Chapa directly from the cart', function () {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();

    $this->actingAs($user)
        ->post(route('cart.store', $product), [
            'variant_id' => $product->variants->first()->id,
        ])
        ->assertRedirect(route('products.show', $product));

    $this->get(route('checkout.show'))->assertRedirect(route('cart.index'));

    Http::fake([
        'https://api.chapa.co/v1/transaction/initialize' => Http::response([
            'status' => 'success',
            'data' => ['checkout_url' => 'https://checkout.chapa.co/test-payment'],
        ]),
    ]);

    $this->post(route('checkout.store'))->assertRedirect('https://checkout.chapa.co/test-payment');

    $this->assertDatabaseCount('lunar_orders', 1);
});

test('headless checkout uses the account identity without billing fields', function (): void {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();

    $this->actingAs($user)->post(route('cart.store', $product), [
        'variant_id' => $product->variants->first()->id,
    ])->assertRedirect();

    Http::fake([
        'https://api.chapa.co/v1/transaction/initialize' => Http::response([
            'status' => 'success',
            'data' => ['checkout_url' => 'https://checkout.chapa.co/test-payment'],
        ]),
    ]);

    $this->actingAs($user)
        ->post(route('checkout.store'))
        ->assertRedirect('https://checkout.chapa.co/test-payment');

    $order = Order::query()->sole();

    expect($order->billingAddress()->exists())->toBeFalse();
    Http::assertSent(fn ($request): bool => $request['email'] === $user->email
        && ! array_key_exists('line_one', $request->data())
        && ! array_key_exists('phone_number', $request->data()));
});

test('checkout sends a normalized authenticated account email to Chapa', function (): void {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $user->update(['email' => 'Buyer@Example.com ']);
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();

    $this->actingAs($user)->post(route('cart.store', $product), [
        'variant_id' => $product->variants->first()->id,
    ])->assertRedirect();

    Http::fake([
        'https://api.chapa.co/v1/transaction/initialize' => Http::response([
            'status' => 'success',
            'data' => ['checkout_url' => 'https://checkout.chapa.co/test-payment'],
        ]),
    ]);

    $this->actingAs($user)
        ->post(route('checkout.store'))
        ->assertRedirect('https://checkout.chapa.co/test-payment');

    Http::assertSent(fn ($request): bool => $request['email'] === 'buyer@example.com');
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
        ->assertJsonPath('items.0.name', $product->name)
        ->assertHeader('content-type', 'application/json');
});

test('product variants render as visual radio choices with configurable presentation', function (): void {
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();
    $variant = $product->variants->firstOrFail();
    $variant->update(['presentation_icon' => 'code']);

    $this->get(route('products.show', $product))
        ->assertSuccessful()
        ->assertSee('Choose an option')
        ->assertSee('type="radio"', false)
        ->assertDontSee('<select', false)
        ->assertSee('Digital license');

    $variant->update(['presentation_image' => 'images/marketplace/ledgerly.webp']);

    $this->get(route('products.show', $product))
        ->assertSee('/images/marketplace/ledgerly.webp');
});

test('the configured variant name is shown in product and cart presentation', function (): void {
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();
    $variant = $product->variants->firstOrFail();
    $variant->update(['variant_name' => 'Professional license']);

    $this->get(route('products.show', $product))
        ->assertSuccessful()
        ->assertSee('Professional license');

    $this->postJson(route('cart.store', $product), [
        'variant_id' => $variant->getKey(),
    ])
        ->assertSuccessful()
        ->assertJsonPath('items.0.option', 'Professional license');
});

test('storefront keeps the footer at the bottom of the page layout', function (): void {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('<body class="flex min-h-screen flex-col', false)
        ->assertSee('<main class="flex-1">', false)
        ->assertSee('<footer class="mt-auto', false);
});
