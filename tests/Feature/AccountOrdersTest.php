<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Lunar\Core\Models\Order;

beforeEach(function () {
    $this->seed();
    config()->set('services.chapa.secret_key', 'test-secret');
});

test('customers can view completed orders', function () {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();

    $this->actingAs($user)
        ->post(route('cart.store', $product), [
            'variant_id' => $product->variants->first()->id,
        ])
        ->assertRedirect(route('products.show', $product));

    Http::fake([
        'https://api.chapa.co/v1/transaction/initialize' => Http::response([
            'status' => 'success',
            'data' => ['checkout_url' => 'https://checkout.chapa.co/test-payment'],
        ]),
    ]);

    $this->post(route('checkout.store'))->assertRedirect('https://checkout.chapa.co/test-payment');

    $order = Order::query()->latest('id')->firstOrFail();
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

    $this->get(route('payments.chapa.return', ['tx_ref' => $transactionReference]))
        ->assertRedirect(route('checkout.complete', $order));

    $this->get(route('account.orders'))
        ->assertSuccessful()
        ->assertSee('Paid')
        ->assertSee('ETB')
        ->assertSee('data-account-route-select', false)
        ->assertSee('data-account-content', false)
        ->assertSee('Previous orders');

    expect($order->lines()->firstOrFail()->option)
        ->toBe($product->variants->firstOrFail()->variant_name);
});
