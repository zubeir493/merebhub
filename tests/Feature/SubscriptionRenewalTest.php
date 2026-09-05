<?php

use App\Models\Product;
use App\Models\User;
use Lunar\Core\Models\Country;
use Lunar\Core\Models\Order;

beforeEach(function () {
    $this->seed();
});

test('verified customers can reach Lunar checkout with an active cart', function () {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();

    $this->actingAs($user)
        ->post(route('cart.store', $product), ['variant_id' => $product->variants->first()->id])
        ->assertRedirect(route('cart.index'));

    $this->get(route('checkout.show'))
        ->assertSuccessful()
        ->assertSee('Billing details');

    $country = Country::query()->where('iso3', 'ETH')->firstOrFail();

    $response = $this->post(route('checkout.store'), [
        'first_name' => 'Demo',
        'last_name' => 'Buyer',
        'contact_email' => $user->email,
        'line_one' => 'Bole Road',
        'city' => 'Addis Ababa',
        'postcode' => '1000',
        'country_id' => $country->id,
    ]);

    $this->assertDatabaseCount('lunar_orders', 1);
    $order = Order::query()->firstOrFail();

    $response->assertRedirect(route('checkout.complete', $order));
    $this->get(route('checkout.complete', $order))->assertSuccessful();
});

test('legacy subscription renewal routes are no longer exposed', function () {
    $this->post('/account/subscriptions/1/renew')->assertNotFound();
});
