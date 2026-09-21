<?php

use App\Domain\Fulfillment\Enums\AssetScanStatus;
use App\Domain\Fulfillment\Enums\FulfillmentUnitStatus;
use App\Models\Credential;
use App\Models\DownloadableAsset;
use App\Models\Entitlement;
use App\Models\FulfillmentUnit;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;
use Lunar\Core\Models\Order;
use Lunar\Core\Models\OrderLine;

function createCredentialEntitlement(User $user, string $productName, string $secret = 'MH-TEST-LICENSE-KEY'): Credential
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
        'idempotency_key' => 'test:credential:'.Str::uuid(),
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
        'secret' => $secret,
    ]);
}

test('a customer sees only their purchases and license records', function (): void {
    $customer = User::factory()->create();
    createCredentialEntitlement($customer, 'Customer Product');
    createCredentialEntitlement(User::factory()->create(), 'Other Customer Product');

    $this->actingAs($customer)
        ->get(route('account.purchases'))
        ->assertSuccessful()
        ->assertSee('Customer Product')
        ->assertDontSee('Other Customer Product')
        ->assertSee('Software licenses')
        ->assertSee('data-mh-copy-license', false)
        ->assertSee('data-reveal-credential', false)
        ->assertSee('Offline .lic activation')
        ->assertDontSee('data-license-key', false)
        ->assertDontSee('MH-TEST-LICENSE-KEY');
});

test('an entitled customer can download a license text file with product and variant details', function (): void {
    $customer = User::factory()->create();
    $credential = createCredentialEntitlement($customer, 'Owned Product');

    $this->actingAs($customer)
        ->get(route('credentials.license-text', $credential->public_id))
        ->assertSuccessful()
        ->assertHeader('content-type', 'text/plain; charset=utf-8')
        ->assertHeader('content-disposition', 'attachment; filename="owned-product-license.txt"')
        ->assertSee('Product: Owned Product')
        ->assertSee('Variant: Standard license')
        ->assertSee('License key: MH-TEST-LICENSE-KEY');
});

test('an active purchase lists only downloadable assets that passed scanning', function (): void {
    $customer = User::factory()->create();
    $credential = createCredentialEntitlement($customer, 'Downloadable Product');
    $productId = $credential->entitlement->product_id;

    DownloadableAsset::query()->create([
        'product_id' => $productId,
        'disk' => 'private',
        'path' => 'products/release.zip',
        'filename' => 'release.zip',
        'scan_status' => AssetScanStatus::Clean,
    ]);
    DownloadableAsset::query()->create([
        'product_id' => $productId,
        'disk' => 'private',
        'path' => 'products/pending.zip',
        'filename' => 'pending.zip',
        'scan_status' => AssetScanStatus::Pending,
    ]);

    $this->actingAs($customer)
        ->get(route('account.purchases'))
        ->assertSuccessful()
        ->assertSee('release.zip')
        ->assertDontSee('pending.zip');

    $this->actingAs($customer)
        ->get(route('account.downloads'))
        ->assertSuccessful()
        ->assertSee('Downloads')
        ->assertSee('release.zip')
        ->assertSee('Download')
        ->assertDontSee('pending.zip');
});

test('an entitled customer can reveal a credential and the reveal is audited without the secret', function (): void {
    $customer = User::factory()->create();
    $credential = createCredentialEntitlement($customer, 'Owned Product');

    $response = $this->actingAs($customer)
        ->postJson(route('credentials.reveal', $credential->public_id));

    $response->assertSuccessful()
        ->assertJsonPath('credential.id', $credential->public_id)
        ->assertJsonPath('credential.secret', 'MH-TEST-LICENSE-KEY')
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
        ->assertHeader('Referrer-Policy', 'no-referrer');

    expect($credential->fresh()->revealed_at)->not->toBeNull();
    $this->assertDatabaseHas('audit_events', [
        'event' => 'credential.revealed',
        'actor_id' => $customer->getKey(),
        'subject_id' => $credential->getKey(),
    ]);
    $this->assertDatabaseMissing('audit_events', ['metadata->secret' => 'MH-TEST-LICENSE-KEY']);
});

test('another customer cannot view or reveal a credential', function (): void {
    $owner = User::factory()->create();
    $credential = createCredentialEntitlement($owner, 'Private Product');

    $this->actingAs(User::factory()->create())
        ->postJson(route('credentials.reveal', $credential->public_id))
        ->assertNotFound();

    expect($credential->fresh()->revealed_at)->toBeNull();
    $this->assertDatabaseMissing('audit_events', ['event' => 'credential.revealed']);
});

test('an inactive entitlement cannot reveal its credential', function (): void {
    $customer = User::factory()->create();
    $credential = createCredentialEntitlement($customer, 'Inactive Product');
    $credential->entitlement()->update(['status' => 'revoked']);

    $this->actingAs($customer)
        ->postJson(route('credentials.reveal', $credential->public_id))
        ->assertNotFound();

    expect($credential->fresh()->revealed_at)->toBeNull();
    $this->assertDatabaseMissing('audit_events', ['event' => 'credential.revealed']);
});

test('credential reveal requires a verified signed-in customer', function (): void {
    $unverifiedCustomer = User::factory()->unverified()->create();
    $credential = createCredentialEntitlement($unverifiedCustomer, 'Verified Product');

    $this->postJson(route('credentials.reveal', $credential->public_id))
        ->assertRedirect(route('login'));

    $this->actingAs($unverifiedCustomer)
        ->get(route('account.purchases'))
        ->assertSuccessful()
        ->assertSee('Verify your email to reveal this key')
        ->assertDontSee('data-reveal-credential');

    $this->actingAs($unverifiedCustomer)
        ->postJson(route('credentials.reveal', $credential->public_id))
        ->assertForbidden();
});

test('credential reveal is rate limited per signed-in customer', function (): void {
    $customer = User::factory()->create();
    $credential = createCredentialEntitlement($customer, 'Rate Limited Product');
    $this->actingAs($customer);

    for ($attempt = 0; $attempt < 6; $attempt++) {
        $this->postJson(route('credentials.reveal', $credential->public_id))
            ->assertSuccessful();
    }

    $this->postJson(route('credentials.reveal', $credential->public_id))
        ->assertTooManyRequests();
});
