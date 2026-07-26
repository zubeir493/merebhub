<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\ProvisionOrderLicenseJob;
use App\Models\Author;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('one Chapa checkout contains products from different vendors', function () {
    Http::fake([
        'api.chapa.test/v1/transaction/initialize' => Http::response([
            'status' => 'success',
            'data' => ['checkout_url' => 'https://checkout.chapa.test/session/abc'],
        ]),
    ]);
    config([
        'services.chapa.api_url' => 'https://api.chapa.test/v1',
        'services.chapa.secret_key' => 'CHASECK_TEST',
    ]);
    $buyer = User::factory()->create();
    $firstProduct = Product::factory()->for(Author::factory())->published()->create();
    $secondProduct = Product::factory()->for(Author::factory())->published()->create();
    $firstPlan = ProductPlan::factory()->for($firstProduct)->create(['price_minor' => 125000]);
    $secondPlan = ProductPlan::factory()->for($secondProduct)->create(['price_minor' => 275000]);
    CartItem::factory()->for($buyer)->for($firstProduct)->for($firstPlan, 'productPlan')->create();
    CartItem::factory()->for($buyer)->for($secondProduct)->for($secondPlan, 'productPlan')->create();

    $this->actingAs($buyer)
        ->post(route('cart.checkout'))
        ->assertRedirect('https://checkout.chapa.test/session/abc');

    $order = Order::sole();

    expect($order->status)->toBe(OrderStatus::AwaitingPayment)
        ->and($order->total_minor)->toBe(400000)
        ->and($order->items)->toHaveCount(2)
        ->and($order->payments)->toHaveCount(1)
        ->and($order->payments->first()->status)->toBe(PaymentStatus::Pending)
        ->and($buyer->cartItems()->count())->toBe(0);

    Http::assertSent(fn ($request): bool => $request['amount'] === '4000.00'
        && $request['currency'] === 'ETB'
        && $request['tx_ref'] === $order->transaction_reference);
});

test('a signed verified Chapa webhook pays and fulfills an order only once', function () {
    Queue::fake();
    config([
        'services.chapa.api_url' => 'https://api.chapa.test/v1',
        'services.chapa.secret_key' => 'CHASECK_TEST',
        'services.chapa.webhook_secret' => 'webhook-secret',
    ]);
    $buyer = User::factory()->create();
    $product = Product::factory()->published()->create();
    $plan = ProductPlan::factory()->for($product)->create(['price_minor' => 125000]);
    $order = Order::factory()->for($buyer, 'buyer')->create([
        'product_id' => null,
        'public_id' => fake()->uuid(),
        'transaction_reference' => 'MH-VERIFIED-1',
        'subtotal_minor' => 125000,
        'total_minor' => 125000,
        'status' => OrderStatus::AwaitingPayment,
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'product_plan_id' => $plan->id,
        'quantity' => 1,
        'unit_amount' => '1250.00',
        'total' => '1250.00',
        'product_name' => $product->name,
        'plan_name' => $plan->name,
        'unit_amount_minor' => 125000,
        'total_minor' => 125000,
        'currency' => 'ETB',
        'primary_author_snapshot' => [
            'id' => $product->author_id,
            'name' => $product->author->name,
            'slug' => $product->author->slug,
        ],
        'commission_basis_points' => 7000,
        'platform_share_minor' => 37500,
        'author_share_minor' => 87500,
    ]);
    $order->payments()->create([
        'provider_reference' => 'MH-VERIFIED-1',
        'amount_minor' => 125000,
    ]);
    Http::fake([
        'api.chapa.test/v1/transaction/verify/MH-VERIFIED-1' => Http::response([
            'status' => 'success',
            'data' => [
                'tx_ref' => 'MH-VERIFIED-1',
                'reference' => 'AP-123',
                'amount' => '1250.00',
                'currency' => 'ETB',
                'status' => 'success',
            ],
        ]),
    ]);
    $payload = json_encode([
        'tx_ref' => 'MH-VERIFIED-1',
        'ref_id' => 'AP-123',
        'status' => 'success',
    ], JSON_THROW_ON_ERROR);
    $headers = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CHAPA_SIGNATURE' => hash_hmac('sha256', $payload, 'webhook-secret'),
    ];

    $this->call('POST', route('webhooks.chapa'), [], [], [], $headers, $payload)
        ->assertSuccessful()
        ->assertJson(['status' => 'accepted']);
    $this->call('POST', route('webhooks.chapa'), [], [], [], $headers, $payload)
        ->assertSuccessful()
        ->assertJson(['status' => 'already_processed']);

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->payments()->first()->status)->toBe(PaymentStatus::Successful)
        ->and($order->earnings()->count())->toBe(1)
        ->and($order->earnings()->first()->final_author_earnings_minor)->toBe(87500);
    Queue::assertPushed(ProvisionOrderLicenseJob::class, 1);
});

test('Chapa amount mismatches never mark an order paid', function () {
    Queue::fake();
    config([
        'services.chapa.api_url' => 'https://api.chapa.test/v1',
        'services.chapa.secret_key' => 'CHASECK_TEST',
        'services.chapa.webhook_secret' => 'webhook-secret',
    ]);
    $order = Order::factory()->create([
        'public_id' => fake()->uuid(),
        'transaction_reference' => 'MH-MISMATCH-1',
        'total_minor' => 125000,
        'status' => OrderStatus::AwaitingPayment,
    ]);
    $order->payments()->create([
        'provider_reference' => 'MH-MISMATCH-1',
        'amount_minor' => 125000,
    ]);
    Http::fake([
        '*' => Http::response([
            'data' => [
                'tx_ref' => 'MH-MISMATCH-1',
                'reference' => 'AP-999',
                'amount' => '1.00',
                'currency' => 'ETB',
                'status' => 'success',
            ],
        ]),
    ]);
    $payload = json_encode(['tx_ref' => 'MH-MISMATCH-1', 'status' => 'success'], JSON_THROW_ON_ERROR);

    $this->call('POST', route('webhooks.chapa'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CHAPA_SIGNATURE' => hash_hmac('sha256', $payload, 'webhook-secret'),
    ], $payload)->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::PaymentFailed);
    Queue::assertNothingPushed();
});
