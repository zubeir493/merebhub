<?php

use App\Domain\Fulfillment\Enums\FulfillmentUnitStatus;
use App\Models\Credential;
use App\Models\Entitlement;
use App\Models\FulfillmentUnit;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Lunar\Core\Models\Order;
use Lunar\Core\Models\OrderLine;

function createOfflineCredential(User $user, string $productName): Credential
{
    $product = Product::factory()->create(['name' => ['en' => $productName]]);
    $order = Order::factory()->placed()->create(['user_id' => $user->getKey()]);
    $orderLine = OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'type' => 'digital',
        'requires_shipping' => false,
        'requires_fulfilment' => false,
    ]);
    $unit = FulfillmentUnit::query()->create([
        'order_id' => $order->getKey(),
        'order_line_id' => $orderLine->getKey(),
        'product_id' => $product->getKey(),
        'type' => 'license',
        'provider' => 'fake-keygen',
        'status' => FulfillmentUnitStatus::Completed,
        'idempotency_key' => 'test:offline:'.Str::uuid(),
    ]);
    $entitlement = Entitlement::query()->create([
        'user_id' => $user->getKey(),
        'order_id' => $order->getKey(),
        'order_line_id' => $orderLine->getKey(),
        'product_id' => $product->getKey(),
        'fulfillment_unit_id' => $unit->getKey(),
        'type' => 'license',
        'status' => 'active',
        'provider' => 'fake-keygen',
        'external_id' => 'fake-'.Str::random(12),
    ]);

    return Credential::query()->create([
        'entitlement_id' => $entitlement->getKey(),
        'type' => 'license_key',
        'secret' => 'MH-OFFLINE-LICENSE-KEY',
    ]);
}

test('an entitled customer can generate an offline license file through the middleware', function (): void {
    $customer = User::factory()->create();
    $credential = createOfflineCredential($customer, 'Offline Product');
    $payload = rtrim(strtr(base64_encode('key='.rawurlencode($credential->secret).';product=offline'), '+/', '-_'), '=');
    $requestContents = 'MRBREQ1.'.$payload;

    config()->set('services.keygen.middleware_url', 'http://127.0.0.1:8000');
    config()->set('services.keygen.admin_token', 'middleware-token');
    Http::preventStrayRequests();
    Http::fake([
        'http://127.0.0.1:8000/v1/offline/license-files' => Http::response([
            'filename' => 'desktop-license.lic',
            'lic_content' => "-----BEGIN LICENSE FILE-----\noffline\n-----END LICENSE FILE-----",
        ]),
    ]);

    $this->actingAs($customer)
        ->post(route('credentials.offline-file'), [
            'offline_request' => UploadedFile::fake()->createWithContent('activation.lreq', $requestContents),
        ])
        ->assertSuccessful()
        ->assertHeader('content-type', 'text/plain; charset=utf-8')
        ->assertHeader('content-disposition', 'attachment; filename="desktop-license.lic"')
        ->assertSee('BEGIN LICENSE FILE');

    Http::assertSent(function ($request) use ($requestContents): bool {
        return $request->url() === 'http://127.0.0.1:8000/v1/offline/license-files'
            && $request->hasHeader('Authorization', 'Bearer middleware-token')
            && $request->hasHeader('X-MerebHub-Admin-Token', 'middleware-token')
            && $request->data()['request_file_contents'] === $requestContents;
    });

    $this->assertDatabaseHas('audit_events', [
        'event' => 'credential.offline_activation_generated',
        'actor_id' => $customer->getKey(),
        'subject_id' => $credential->getKey(),
    ]);
});

test('customers cannot generate an offline file for an unowned license', function (): void {
    $customer = User::factory()->create();
    createOfflineCredential(User::factory()->create(), 'Other Product');
    $payload = rtrim(strtr(base64_encode('key='.rawurlencode('MH-OTHER-LICENSE-KEY').';product=other'), '+/', '-_'), '=');

    Http::preventStrayRequests();

    $this->actingAs($customer)
        ->post(route('credentials.offline-file'), [
            'offline_request' => UploadedFile::fake()->createWithContent('activation.lreq', 'MRBREQ1.'.$payload),
        ])
        ->assertSessionHasErrors('offline_request');
});

test('customers cannot use a non-lreq upload for offline activation', function (): void {
    $customer = User::factory()->create();

    Http::preventStrayRequests();

    $this->actingAs($customer)
        ->post(route('credentials.offline-file'), [
            'offline_request' => UploadedFile::fake()->createWithContent('activation.txt', 'not a request file'),
        ])
        ->assertSessionHasErrors('offline_request');
});

test('an entitled Keygen customer can download a generated offline license file', function (): void {
    $customer = User::factory()->create();
    $credential = createOfflineCredential($customer, 'Offline Product');
    $credential->entitlement()->update([
        'provider' => 'keygen',
        'external_id' => 'keygen-license-123',
    ]);

    config()->set('services.keygen.url', 'https://keygen.localhost:8443');
    config()->set('services.keygen.account_id', 'account-123');
    config()->set('services.keygen.api_token', 'server-token');
    config()->set('services.keygen.verify', false);
    Http::preventStrayRequests();
    Http::fake([
        'https://keygen.localhost:8443/v1/accounts/account-123/licenses/keygen-license-123/actions/check-out*' => Http::response("-----BEGIN LICENSE FILE-----\noffline\n-----END LICENSE FILE-----"),
    ]);

    $this->actingAs($customer)
        ->get(route('credentials.offline-file.direct', $credential->public_id))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/octet-stream')
        ->assertHeader('content-disposition', 'attachment; filename="offline-product-offline-license.lic"')
        ->assertSee('BEGIN LICENSE FILE');

    $this->assertDatabaseHas('audit_events', [
        'event' => 'credential.offline_file_downloaded',
        'actor_id' => $customer->getKey(),
        'subject_id' => $credential->getKey(),
    ]);
});
