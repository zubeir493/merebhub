<?php

use App\Actions\ProvisionOrderLicense;
use App\Enums\LicenseStatus;
use App\Livewire\HomeCatalog;
use App\Mail\PurchaseConfirmation;
use App\Models\AppSubmission;
use App\Models\Author;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('the storefront renders published catalog content', function () {
    Product::factory()->published()->create([
        'name' => 'Soko Inventory',
        'cover_path' => 'images/marketplace/soko-inventory.webp',
        'is_featured' => true,
    ]);

    $this->get('/')->assertSuccessful()->assertSee('Soko Inventory')->assertSee('Top selling this week');
});

test('flash messages render as dismissible timed toasts', function () {
    $this->withSession(['status' => 'Cart updated.'])
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Cart updated.')
        ->assertSee('Dismiss notification')
        ->assertSee('role="status"', false)
        ->assertSee('5000', false);
});

test('the Livewire catalog filters by category', function () {
    Product::factory()->published()->create(['category' => 'Games']);
    Product::factory()->published()->create(['category' => 'Business']);

    Livewire::test(HomeCatalog::class)
        ->set('category', 'Games')
        ->assertSet('category', 'Games')
        ->assertViewHas('products', fn ($products): bool => $products->count() === 1 && $products->first()->category === 'Games');
});

test('global search has a dedicated results page for software and makers', function () {
    $author = Author::factory()->verified()->create(['name' => 'Soko Labs']);
    Product::factory()->for($author)->published()->create(['name' => 'Soko Inventory']);
    Product::factory()->published()->create(['name' => 'Unrelated Product']);

    $this->get(route('vendors.show', $author))
        ->assertSuccessful()
        ->assertSee('action="'.route('search').'"', false);

    $this->get(route('search', ['q' => 'Soko']))
        ->assertSuccessful()
        ->assertSee('Results for “Soko”')
        ->assertSee('Soko Inventory')
        ->assertSee('Soko Labs')
        ->assertDontSee('Unrelated Product');
});

test('buyers can update cart quantities', function () {
    $buyer = User::factory()->create();
    $product = Product::factory()->published()->create();
    $plan = ProductPlan::factory()->for($product)->create(['price_minor' => 125000]);
    $cartItem = CartItem::factory()
        ->for($buyer)
        ->for($product)
        ->for($plan, 'productPlan')
        ->create();

    $this->actingAs($buyer)
        ->patch(route('cart.update', $cartItem), ['quantity' => 3])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($cartItem->refresh()->quantity)->toBe(3);

    $this->actingAs($buyer)
        ->get(route('cart.index'))
        ->assertSuccessful()
        ->assertSee('Decrease quantity')
        ->assertSee('Increase quantity')
        ->assertSee('3,750.00 ETB');
});

test('the cart badge counts item types instead of total quantity', function () {
    $buyer = User::factory()->create();

    foreach ([2, 3] as $quantity) {
        $product = Product::factory()->published()->create();
        $plan = ProductPlan::factory()->for($product)->create();

        CartItem::factory()
            ->for($buyer)
            ->for($product)
            ->for($plan, 'productPlan')
            ->create(['quantity' => $quantity]);
    }

    $this->actingAs($buyer)
        ->get(route('cart.index'))
        ->assertSuccessful()
        ->assertSee('aria-label="2 item types in cart"', false);
});

test('wishlist items can be moved to the cart with an active plan', function () {
    $buyer = User::factory()->create();
    $product = Product::factory()->published()->create();
    $plan = ProductPlan::factory()->for($product)->create();
    WishlistItem::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
    ]);

    $this->actingAs($buyer)
        ->get(route('wishlist.index'))
        ->assertSuccessful()
        ->assertSee('name="product_plan_id" value="'.$plan->id.'"', false);
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
    $this->get('/account/purchases')->assertSuccessful()->assertSee('Previous orders');
});

test('only admins can access the filament admin panel', function () {
    $buyer = User::factory()->create(['is_admin' => false]);
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($buyer)->get('/admin')->assertForbidden();
    $this->actingAs($admin)->get('/admin')->assertSuccessful();
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
        'fulfillment_type' => 'license_key',
        'payment_model' => 'one_time',
        'build' => UploadedFile::fake()->create('great-app.zip', 100, 'application/zip'),
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    $submission = AppSubmission::firstOrFail();
    Storage::disk('local')->assertExists($submission->file_path);
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
    $plan = ProductPlan::factory()->for($product)->create([
        'keygen_policy_id' => 'policy',
    ]);
    $order = Order::factory()->paid()->for($product)->create([
        'buyer_email' => 'buyer@example.com',
        'amount' => $product->price,
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'product_plan_id' => $plan->id,
        'quantity' => 1,
        'unit_amount' => $product->price,
        'total' => $product->price,
        'product_name' => $product->name,
        'plan_name' => $plan->name,
        'unit_amount_minor' => $plan->price_minor,
        'total_minor' => $plan->price_minor,
        'currency' => 'ETB',
        'billing_model' => $plan->billing_model,
        'fulfillment_type' => $plan->fulfillment_type,
        'license_configuration' => [
            'keygen_policy_id' => 'policy',
            'activation_limit' => 1,
        ],
    ]);

    $license = app(ProvisionOrderLicense::class)->handle($order);

    expect($license?->status)->toBe(LicenseStatus::Active);
    Mail::assertQueued(PurchaseConfirmation::class);
});
