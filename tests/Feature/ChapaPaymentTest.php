<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Lunar\Core\Models\Country;
use Lunar\Core\Models\Order;

beforeEach(function (): void {
    $this->seed();
    config()->set('lunar.payments.default', 'chapa');
    config()->set('services.chapa.secret_key', 'test-secret');
    config()->set('services.chapa.webhook_secret', 'webhook-secret');
});

function startChapaCheckout(): array
{
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();

    test()->actingAs($user)->post(route('cart.store', $product), [
        'variant_id' => $product->variants->first()->id,
    ])->assertRedirect(route('cart.index'));

    Http::fake([
        'https://api.chapa.co/v1/transaction/initialize' => Http::response([
            'status' => 'success',
            'data' => ['checkout_url' => 'https://checkout.chapa.co/test-payment'],
        ]),
    ]);

    test()->actingAs($user)->post(route('checkout.store'), [
        'first_name' => 'Demo',
        'last_name' => 'Buyer',
        'contact_email' => $user->email,
        'line_one' => 'Bole Road',
        'city' => 'Addis Ababa',
        'postcode' => '1000',
        'country_id' => Country::query()->where('iso3', 'ETH')->firstOrFail()->id,
    ])->assertRedirect('https://checkout.chapa.co/test-payment');

    $order = Order::query()->latest('id')->firstOrFail();

    return [$user, $order, (string) data_get($order->meta, 'chapa.tx_ref')];
}

test('checkout initializes a Chapa payment and preserves the draft order', function (): void {
    [, $order, $transactionReference] = startChapaCheckout();

    expect($transactionReference)->not->toBeEmpty();
    expect($order->placed_at)->toBeNull();
    expect($order->meta['chapa']['status'])->toBe('pending');
    $this->assertDatabaseCount('lunar_transactions', 0);

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://api.chapa.co/v1/transaction/initialize'
            && $request['currency'] === 'ETB'
            && $request['amount'] === '2590.00'
            && $request['tx_ref'] !== '';
    });
});

test('the Chapa return verifies and records one captured transaction', function (): void {
    [$user, $order, $transactionReference] = startChapaCheckout();

    Http::fake([
        'https://api.chapa.co/v1/transaction/verify/*' => Http::response([
            'status' => 'success',
            'data' => [
                'status' => 'success',
                'tx_ref' => $transactionReference,
                'amount' => '2590.00',
                'currency' => 'ETB',
            ],
        ]),
    ]);

    $this->actingAs($user)
        ->get(route('payments.chapa.return', ['tx_ref' => $transactionReference]))
        ->assertRedirect(route('checkout.complete', $order));

    $this->assertDatabaseHas('lunar_transactions', [
        'order_id' => $order->id,
        'driver' => 'chapa',
        'reference' => $transactionReference,
        'success' => true,
        'amount' => $order->total,
        'type' => 'capture',
    ]);
    $this->assertDatabaseCount('lunar_transactions', 1);
    expect($order->fresh()->placed_at)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('payments.chapa.return', ['tx_ref' => $transactionReference]))
        ->assertRedirect(route('checkout.complete', $order));

    $this->assertDatabaseCount('lunar_transactions', 1);
});

test('a Chapa amount mismatch does not place the order', function (): void {
    [$user, $order, $transactionReference] = startChapaCheckout();

    Http::fake([
        'https://api.chapa.co/v1/transaction/verify/*' => Http::response([
            'status' => 'success',
            'data' => [
                'status' => 'success',
                'tx_ref' => $transactionReference,
                'amount' => '1.00',
                'currency' => 'ETB',
            ],
        ]),
    ]);

    $this->actingAs($user)
        ->get(route('payments.chapa.return', ['tx_ref' => $transactionReference]))
        ->assertRedirect(route('cart.index'))
        ->assertSessionHasErrors('payment');

    expect($order->fresh()->placed_at)->toBeNull();
    $this->assertDatabaseCount('lunar_transactions', 0);
});

test('the Chapa webhook requires a valid signature', function (): void {
    $this->postJson(route('payments.chapa.webhook'), ['tx_ref' => 'unknown'])
        ->assertUnauthorized();
});

test('the Chapa webhook verifies a signed payment without CSRF', function (): void {
    [$user, $order, $transactionReference] = startChapaCheckout();
    $payload = ['tx_ref' => $transactionReference];

    Http::fake([
        'https://api.chapa.co/v1/transaction/verify/*' => Http::response([
            'status' => 'success',
            'data' => [
                'status' => 'success',
                'tx_ref' => $transactionReference,
                'amount' => '2590.00',
                'currency' => 'ETB',
            ],
        ]),
    ]);

    $this->withHeader(
        'x-chapa-signature',
        hash_hmac('sha256', json_encode($payload), 'webhook-secret'),
    )->postJson(route('payments.chapa.webhook'), $payload)
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    $this->assertDatabaseHas('lunar_transactions', [
        'order_id' => $order->id,
        'reference' => $transactionReference,
        'success' => true,
    ]);
});
