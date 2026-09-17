<?php

use App\Domain\Fulfillment\Actions\CreateFulfillmentUnitsAction;
use App\Domain\Fulfillment\Actions\ProvisionFulfillmentUnitAction;
use App\Domain\Fulfillment\Contracts\LicenseProvider;
use App\Domain\Fulfillment\Enums\AssetScanStatus;
use App\Domain\Fulfillment\Enums\FulfillmentUnitStatus;
use App\Domain\Fulfillment\Providers\FakeKeygenLicenseProvider;
use App\Exceptions\Domain\Fulfillment\Exceptions\LicenseProviderException;
use App\Jobs\ProcessFulfillmentOutboxMessage;
use App\Models\Credential;
use App\Models\DownloadableAsset;
use App\Models\Entitlement;
use App\Models\FulfillmentAttempt;
use App\Models\FulfillmentUnit;
use App\Models\OutboxMessage;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LicenseProvisionedNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Lunar\Core\Models\Country;
use Lunar\Core\Models\Order;

beforeEach(function (): void {
    $this->seed();
});

function createPaidFulfillmentOrder(): array
{
    Queue::fake([ProcessFulfillmentOutboxMessage::class]);

    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();

    test()->actingAs($user)->post(route('cart.store', $product), [
        'variant_id' => $product->variants->first()->id,
    ])->assertRedirect(route('products.show', $product));

    config()->set('lunar.payments.default', 'chapa');
    config()->set('services.chapa.secret_key', 'test-secret');

    Http::preventStrayRequests();
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
        'payment_method' => 'chapa',
    ])->assertRedirect('https://checkout.chapa.co/test-payment');

    $order = Order::query()->latest('id')->firstOrFail();
    $transactionReference = (string) data_get($order->meta, 'chapa.tx_ref');

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

    test()->actingAs($user)
        ->get(route('payments.chapa.return', ['tx_ref' => $transactionReference]))
        ->assertRedirect(route('checkout.complete', $order));

    return [$user, $order->fresh(), $transactionReference];
}

test('a verified payment creates one idempotent entitlement through the fulfillment outbox', function (): void {
    [$user, $order] = createPaidFulfillmentOrder();
    $message = OutboxMessage::query()->where('event', 'order.fulfillment.requested')->firstOrFail();
    $provider = new FakeKeygenLicenseProvider;
    app()->instance(LicenseProvider::class, $provider);

    Queue::assertPushed(ProcessFulfillmentOutboxMessage::class, function (ProcessFulfillmentOutboxMessage $job) use ($message): bool {
        return $job->outboxMessageId === $message->getKey();
    });

    app(ProcessFulfillmentOutboxMessage::class, ['outboxMessageId' => $message->getKey()])
        ->handle(app(CreateFulfillmentUnitsAction::class), app(ProvisionFulfillmentUnitAction::class));

    expect($order->fresh()->placed_at)->not->toBeNull()
        ->and(FulfillmentUnit::query()->count())->toBe(1)
        ->and(Entitlement::query()->whereBelongsTo($user)->count())->toBe(1);
    $this->assertDatabaseCount('credentials', 1);
    $this->assertDatabaseCount('provider_mirrors', 1);
    expect(Credential::query()->firstOrFail()->toArray())->not->toHaveKey('secret');
    expect(OutboxMessage::query()->findOrFail($message->getKey())->published_at)->not->toBeNull();

    app(ProcessFulfillmentOutboxMessage::class, ['outboxMessageId' => $message->getKey()])
        ->handle(app(CreateFulfillmentUnitsAction::class), app(ProvisionFulfillmentUnitAction::class));

    expect(FulfillmentUnit::query()->count())->toBe(1)
        ->and(Entitlement::query()->whereBelongsTo($user)->count())->toBe(1)
        ->and($provider->provisioned)->toHaveCount(1);
});

test('a provisioned license is emailed to the purchasing customer', function (): void {
    [$user, $order] = createPaidFulfillmentOrder();
    $unit = app(CreateFulfillmentUnitsAction::class)->handle($order)->firstOrFail();
    $provider = new FakeKeygenLicenseProvider;
    app()->instance(LicenseProvider::class, $provider);
    Notification::fake();

    app(ProvisionFulfillmentUnitAction::class)->handle($unit);

    Notification::assertSentTo($user, LicenseProvisionedNotification::class, function (LicenseProvisionedNotification $notification) use ($unit): bool {
        return $notification->entitlementId === $unit->fresh()->entitlement?->getKey();
    });
});

test('an ambiguous provider create recovers without creating a second license', function (): void {
    [, $order] = createPaidFulfillmentOrder();
    $unit = app(CreateFulfillmentUnitsAction::class)->handle($order)->firstOrFail();
    $provider = new FakeKeygenLicenseProvider;
    $provider->ambiguousIdempotencyKeys[$unit->idempotency_key] = true;
    app()->instance(LicenseProvider::class, $provider);

    $entitlement = app(ProvisionFulfillmentUnitAction::class)->handle($unit);

    expect($entitlement->external_id)->toStartWith('fake-')
        ->and(FulfillmentUnit::query()->where('status', FulfillmentUnitStatus::Completed->value)->count())->toBe(1)
        ->and(Entitlement::query()->count())->toBe(1)
        ->and($provider->provisioned)->toHaveCount(1);
});

test('a provider failure records needs attention without failing the paid order', function (): void {
    [, $order] = createPaidFulfillmentOrder();
    $unit = app(CreateFulfillmentUnitsAction::class)->handle($order)->firstOrFail();
    $provider = new FakeKeygenLicenseProvider;
    $provider->failedIdempotencyKeys[$unit->idempotency_key] = true;
    app()->instance(LicenseProvider::class, $provider);

    expect(fn () => app(ProvisionFulfillmentUnitAction::class)->handle($unit))
        ->toThrow(LicenseProviderException::class);

    expect($order->fresh()->placed_at)->not->toBeNull()
        ->and($unit->fresh()->status)->toBe(FulfillmentUnitStatus::NeedsAttention)
        ->and(FulfillmentAttempt::query()->whereBelongsTo($unit)->value('status'))->toBe('failed');
    $this->assertDatabaseCount('entitlements', 0);
});

test('only an entitled customer can receive a signed download for a clean asset', function (): void {
    [$user, $order] = createPaidFulfillmentOrder();
    $unit = app(CreateFulfillmentUnitsAction::class)->handle($order)->firstOrFail();
    $provider = new FakeKeygenLicenseProvider;
    app()->instance(LicenseProvider::class, $provider);
    app(ProvisionFulfillmentUnitAction::class)->handle($unit);

    Storage::fake('private');
    Storage::disk('private')->put('products/demo.zip', 'downloadable content');
    $asset = DownloadableAsset::create([
        'product_id' => $unit->product_id,
        'disk' => 'private',
        'path' => 'products/demo.zip',
        'filename' => 'demo.zip',
        'scan_status' => AssetScanStatus::Clean,
    ]);

    $urlResponse = $this->actingAs($user)->getJson(route('downloads.url', ['downloadableAsset' => $asset->public_id]));

    $urlResponse->assertSuccessful();
    $downloadResponse = $this->actingAs($user)->get($urlResponse->json('url'));
    $downloadResponse->assertSuccessful();
    $this->assertDatabaseHas('audit_events', ['event' => 'entitlement.downloaded']);
    $this->actingAs(User::factory()->create())->get($urlResponse->json('url'))->assertNotFound();
    expect($unit->fresh()->entitlement)->not->toBeNull();
});
