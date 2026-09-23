<?php

use App\Models\Product;

beforeEach(function (): void {
    $this->seed();
});

test('login and register pages display contextual copy when forwarded from wishlist', function (): void {
    $loginResponse = $this->get(route('login', ['intent' => 'wishlist']));

    $loginResponse->assertOk()
        ->assertSee('Sign in to add items to your wishlist')
        ->assertSee(route('register', ['intent' => 'wishlist']));

    $registerResponse = $this->get(route('register', ['intent' => 'wishlist']));

    $registerResponse->assertOk()
        ->assertSee('Create an account to add items to your wishlist')
        ->assertSee(route('login', ['intent' => 'wishlist']));
});

test('login and register pages display contextual copy when forwarded from checkout', function (): void {
    $loginResponse = $this->get(route('login', ['intent' => 'checkout']));

    $loginResponse->assertOk()
        ->assertSee('Sign in to complete your checkout')
        ->assertSee('You need to sign in to complete your checkout and access your purchased software licenses.')
        ->assertSee(route('register', ['intent' => 'checkout']));

    $registerResponse = $this->get(route('register', ['intent' => 'checkout']));

    $registerResponse->assertOk()
        ->assertSee('Create an account to complete your checkout')
        ->assertSee(route('login', ['intent' => 'checkout']));
});

test('product page links guest wishlist button directly to login with wishlist intent', function (): void {
    $product = Product::published()->with('defaultUrl')->firstOrFail();

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSee(route('login', [
            'intent' => 'wishlist',
            'redirect' => route('products.show', $product),
        ]))
        ->assertSee('Sign in to add items to your wishlist');
});

test('cart page links guest checkout button directly to login with checkout intent', function (): void {
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();

    $this->post(route('cart.store', $product), [
        'variant_id' => $product->variants->first()->id,
    ]);

    $this->get(route('cart.index'))
        ->assertOk()
        ->assertSee(route('login', [
            'intent' => 'checkout',
            'redirect' => route('cart.index'),
        ]))
        ->assertSee('Checkout with Chapa');
});

test('unauthenticated access to checkout route preserves checkout intent on login page', function (): void {
    $response = $this->get('/checkout');

    $response->assertRedirect(route('login'));

    $this->followRedirects($response)
        ->assertOk()
        ->assertSee('Sign in to complete your checkout');
});
