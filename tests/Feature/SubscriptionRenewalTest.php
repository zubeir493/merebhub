<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Lunar\Core\Models\Country;
use Lunar\Core\Models\Order;

beforeEach(function () {
    $this->seed();
    config()->set('services.chapa.secret_key', 'test-secret');
});

test('verified customers can reach Lunar checkout with an active cart', function () {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();

    $this->actingAs($user)
        ->post(route('cart.store', $product), ['variant_id' => $product->variants->first()->id])
        ->assertRedirect(route('products.show', $product));

    $this->get(route('checkout.show'))
        ->assertSuccessful()
        ->assertSee('Billing details');

    $country = Country::query()->where('iso3', 'ETH')->firstOrFail();

    Http::fake([
        'https://api.chapa.co/v1/transaction/initialize' => Http::response([
            'status' => 'success',
            'data' => ['checkout_url' => 'https://checkout.chapa.co/test-payment'],
        ]),
    ]);

    $response = $this->post(route('checkout.store'), [
        'first_name' => 'Demo',
        'last_name' => 'Buyer',
        'contact_email' => $user->email,
        'line_one' => 'Bole Road',
        'city' => 'Addis Ababa',
        'postcode' => '1000',
        'country_id' => $country->id,
        'payment_method' => 'chapa',
    ]);

    $this->assertDatabaseCount('lunar_orders', 1);
    $order = Order::query()->firstOrFail();

    $transactionReference = (string) data_get($order->meta, 'chapa.tx_ref');
    Http::fake([
        'https://api.chapa.co/v1/transaction/verify/*' => Http::response([
            'status' => 'success',
            'data' => [
                'status' => 'success',
                'tx_ref' => $transactionReference,
                'amount' => number_format($order->total / 100, 2, '.', ''),
                'currency' => 'ETB',
            ],
        ]),
    ]);

    $response = $this->get(route('payments.chapa.return', ['tx_ref' => $transactionReference]));

    $response->assertRedirect(route('checkout.complete', $order));
    $this->get(route('checkout.complete', $order))->assertSuccessful();
});

test('legacy subscription renewal routes are no longer exposed', function () {
    $this->post('/account/subscriptions/1/renew')->assertNotFound();
});
