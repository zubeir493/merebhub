<?php

use App\Models\Product;
use App\Models\User;
use Lunar\Core\Models\Country;

beforeEach(function () {
    $this->seed();
});

test('checkout shows localized cart item names and creates the order', function () {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::query()->with(['defaultUrl', 'variants'])->firstOrFail();

    $this->actingAs($user)
        ->post(route('cart.store', $product), [
            'variant_id' => $product->variants->first()->id,
        ])
        ->assertRedirect(route('cart.index'));

    $this->get(route('checkout.show'))
        ->assertSuccessful()
        ->assertSee($product->name)
        ->assertDontSee('{"en":');

    $this->post(route('checkout.store'), [
        'first_name' => 'Demo',
        'last_name' => 'Buyer',
        'contact_email' => $user->email,
        'line_one' => 'Bole Road',
        'city' => 'Addis Ababa',
        'postcode' => '1000',
        'country_id' => Country::query()->where('iso3', 'ETH')->firstOrFail()->id,
    ])->assertRedirect();

    $this->assertDatabaseCount('lunar_orders', 1);
});
