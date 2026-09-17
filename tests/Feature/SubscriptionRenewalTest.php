<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Lunar\Core\Models\Order;

beforeEach(function () {
    $this->seed();
    config()->set('services.chapa.secret_key', 'test-secret');
});

test('verified customers can start headless Chapa checkout with an active cart', function () {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();

    $this->actingAs($user)
        ->post(route('cart.store', $product), ['variant_id' => $product->variants->first()->id])
        ->assertRedirect(route('products.show', $product));

    $this->get(route('checkout.show'))->assertRedirect(route('cart.index'));

    Http::fake([
        'https://api.chapa.co/v1/transaction/initialize' => Http::response([
            'status' => 'success',
            'data' => ['checkout_url' => 'https://checkout.chapa.co/test-payment'],
        ]),
    ]);

    $response = $this->post(route('checkout.store'));

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
