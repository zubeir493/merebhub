<?php

use App\Actions\ProvisionOrderLicense;
use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Jobs\ProvisionOrderLicenseJob;
use App\Livewire\HomeCatalog;
use App\Mail\PurchaseConfirmation;
use App\Models\AppSubmission;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\WooCommerceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('the storefront renders published catalog content', function () {
    Product::factory()->published()->create([
        'name' => 'Soko Inventory',
        'cover_path' => 'images/marketplace/soko-inventory.webp',
        'is_featured' => true,
    ]);

    $this->get('/')->assertOk()->assertSee('Soko Inventory')->assertSee('Top selling this week');
});

test('the Livewire catalog filters by category', function () {
    Product::factory()->published()->create(['category' => 'Games']);
    Product::factory()->published()->create(['category' => 'Business']);

    Livewire::test(HomeCatalog::class)
        ->set('category', 'Games')
        ->assertSet('category', 'Games')
        ->assertViewHas('products', fn ($products): bool => $products->count() === 1 && $products->first()->category === 'Games');
});

test('buyers can register and access their purchase library', function () {
    $response = $this->post('/register', [
        'name' => 'New Buyer',
        'email' => 'buyer@example.com',
        'password' => 'strong-password',
        'password_confirmation' => 'strong-password',
    ]);

    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticated();
    $this->get('/account/purchases')->assertOk()->assertSee('Your library');
});

test('only admins can access the filament panel', function () {
    $buyer = User::factory()->create(['is_admin' => false]);
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($buyer)->get('/admin')->assertForbidden();
    $this->actingAs($admin)->get('/admin')->assertOk();
});

test('public submissions store uploaded builds', function () {
    Storage::fake('local');
    config(['filesystems.builds_disk' => 'local']);

    $response = $this->post('/submit', [
        'submitter_name' => 'A Developer',
        'submitter_email' => 'developer@example.com',
        'app_name' => 'Great App',
        'description' => str_repeat('A useful product for Ethiopian teams. ', 3),
        'suggested_price' => 1200,
        'platform' => 'Windows',
        'build' => UploadedFile::fake()->create('great-app.zip', 100, 'application/zip'),
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    $submission = AppSubmission::firstOrFail();
    Storage::disk('local')->assertExists($submission->file_path);
});

test('checkout creates a pending mirror and redirects to WooCommerce', function () {
    config([
        'services.woocommerce.api_url' => 'https://woo.test/wp-json/wc/v3',
        'services.woocommerce.consumer_key' => 'key',
        'services.woocommerce.consumer_secret' => 'secret',
    ]);
    Http::fake([
        'woo.test/*' => Http::response([
            'id' => 9876,
            'order_key' => 'wc_order_abc',
            'currency' => 'ETB',
        ]),
    ]);
    $product = Product::factory()->published()->create(['wc_product_id' => 55]);

    $response = $this->post(route('checkout.start', $product), ['buyer_email' => 'buyer@example.com']);

    $response->assertRedirect('https://woo.test/checkout/order-pay/9876/?pay_for_order=true&key=wc_order_abc');
    $this->assertDatabaseHas('orders', ['wc_order_id' => 9876, 'status' => OrderStatus::Pending->value]);
});

test('published products use the WooCommerce publish status', function () {
    config([
        'services.woocommerce.api_url' => 'https://woo.test/wp-json/wc/v3',
        'services.woocommerce.consumer_key' => 'key',
        'services.woocommerce.consumer_secret' => 'secret',
    ]);
    Http::fake(['woo.test/*' => Http::response(['id' => 123])]);
    $product = Product::factory()->create(['status' => ProductStatus::Published]);

    app(WooCommerceService::class)->createProduct($product);

    Http::assertSent(fn ($request): bool => $request['status'] === 'publish');
});

test('signed WooCommerce webhooks mirror paid orders once and queue fulfillment', function () {
    Queue::fake();
    config(['services.woocommerce.webhook_secret' => 'webhook-secret']);
    $product = Product::factory()->published()->create(['wc_product_id' => 55]);
    $payload = [
        'id' => 4433,
        'status' => 'completed',
        'total' => '2500.00',
        'currency' => 'ETB',
        'billing' => ['email' => 'buyer@example.com'],
        'line_items' => [['product_id' => $product->wc_product_id]],
    ];
    $content = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = base64_encode(hash_hmac('sha256', $content, 'webhook-secret', true));
    $server = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_WC_WEBHOOK_TOPIC' => 'order.updated',
        'HTTP_X_WC_WEBHOOK_SIGNATURE' => $signature,
    ];

    $this->call('POST', '/webhooks/woocommerce', [], [], [], $server, $content)->assertOk();
    $this->call('POST', '/webhooks/woocommerce', [], [], [], $server, $content)
        ->assertOk()
        ->assertJson(['status' => 'already_processed']);

    $this->assertDatabaseHas('orders', ['wc_order_id' => 4433, 'status' => OrderStatus::Paid->value]);
    Queue::assertPushed(ProvisionOrderLicenseJob::class, 1);
});

test('paid orders are licensed through Keygen and emailed', function () {
    Mail::fake();
    config([
        'services.keygen.api_url' => 'https://api.keygen.test',
        'services.keygen.api_token' => 'token',
        'services.keygen.account_id' => 'account',
        'services.keygen.policy_id' => 'policy',
    ]);
    Http::fake([
        'api.keygen.test/*' => Http::response([
            'data' => [
                'id' => 'license-id',
                'attributes' => ['key' => 'ABCDE-FGHIJ-KLMNO', 'expiry' => null],
            ],
        ]),
    ]);
    $product = Product::factory()->published()->create();
    $order = Order::factory()->paid()->for($product)->create([
        'buyer_email' => 'buyer@example.com',
        'amount' => $product->price,
    ]);

    $license = app(ProvisionOrderLicense::class)->handle($order);

    expect($license->status)->toBe(LicenseStatus::Active);
    Mail::assertQueued(PurchaseConfirmation::class);
});
