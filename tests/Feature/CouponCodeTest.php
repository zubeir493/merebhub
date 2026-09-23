<?php

use App\Filament\Admin\Resources\Coupons\CouponResource;
use App\Filament\Admin\Resources\Coupons\Pages\CreateCoupon;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Staff;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Lunar\Core\DiscountTypes\AmountOff;
use Lunar\Core\Facades\CartSession;

beforeEach(function (): void {
    $this->seed();
    config()->set('services.chapa.secret_key', 'test-secret');
});

test('admin panel registers the coupon resource', function (): void {
    expect(Filament::getPanel('lunar')->getResources())
        ->toContain(CouponResource::class);
});

test('admin can access the coupons resource in admin panel', function (): void {
    $staff = Staff::factory()->create(['admin' => true]);

    Filament::setCurrentPanel(Filament::getPanel('lunar'));
    Filament::bootCurrentPanel();

    $this->actingAs($staff, 'staff')
        ->get('/admin/coupons')
        ->assertSuccessful();

    $this->actingAs($staff, 'staff')
        ->get('/admin/coupons/create')
        ->assertSuccessful();
});

test('staff can create a coupon via the Filament form', function (): void {
    $staff = Staff::factory()->create(['admin' => true]);

    Filament::setCurrentPanel(Filament::getPanel('lunar'));
    Filament::bootCurrentPanel();
    $this->actingAs($staff, 'staff');

    Livewire::test(CreateCoupon::class)
        ->fillForm([
            'coupon' => 'TESTCODE25',
            'name' => '25% Special',
            'discount_type' => 'percentage',
            'discount_value' => 25,
            'minimum_spend' => 100,
            'max_uses' => 50,
            'max_uses_per_user' => 1,
            'starts_at' => now()->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $coupon = Coupon::query()->where('coupon', 'TESTCODE25')->first();
    expect($coupon)->not->toBeNull()
        ->and($coupon->discountValue())->toBe(25.0)
        ->and($coupon->isPercentage())->toBeTrue()
        ->and($coupon->minimumSpend())->toBe(100.0)
        ->and($coupon->max_uses)->toBe(50);
});

test('user can apply a valid percentage coupon and cart total is reduced', function (): void {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['variants'])->firstOrFail();
    $variant = $product->variants->firstOrFail();

    Coupon::create([
        'name' => '10% Launch Discount',
        'coupon' => 'MEREB10',
        'type' => AmountOff::class,
        'starts_at' => now()->subDay(),
        'data' => [
            'percentage' => 10,
            'fixed_value' => false,
        ],
    ]);

    $this->actingAs($user)
        ->post(route('cart.store', $product), [
            'variant_id' => $variant->id,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('cart.coupon.apply'), [
            'coupon_code' => 'mereb10',
        ])
        ->assertRedirect(route('cart.index'))
        ->assertSessionHas('status', "Coupon 'MEREB10' applied successfully!");

    $cart = CartSession::current();
    expect($cart->coupon_code)->toBe('MEREB10')
        ->and($cart->discountTotal?->value)->toBeGreaterThan(0)
        ->and($cart->total->value)->toBe($cart->subTotal->value - $cart->discountTotal->value);
});

test('user can apply a valid fixed amount coupon', function (): void {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['variants'])->firstOrFail();
    $variant = $product->variants->firstOrFail();

    Coupon::create([
        'name' => '50 ETB Off',
        'coupon' => 'SAVE50',
        'type' => AmountOff::class,
        'starts_at' => now()->subDay(),
        'data' => [
            'fixed_value' => true,
            'fixed_values' => ['ETB' => 5000],
        ],
    ]);

    $this->actingAs($user)
        ->post(route('cart.store', $product), [
            'variant_id' => $variant->id,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('cart.coupon.apply'), [
            'coupon_code' => 'SAVE50',
        ])
        ->assertRedirect(route('cart.index'));

    $cart = CartSession::current();
    expect($cart->coupon_code)->toBe('SAVE50')
        ->and($cart->discountTotal?->value)->toBe(5000);
});

test('applying an invalid coupon code returns error', function (): void {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['variants'])->firstOrFail();
    $variant = $product->variants->firstOrFail();

    $this->actingAs($user)
        ->post(route('cart.store', $product), [
            'variant_id' => $variant->id,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('cart.coupon.apply'), [
            'coupon_code' => 'NONEXISTENT',
        ])
        ->assertSessionHasErrors(['coupon_code' => 'Invalid discount code.']);
});

test('applying an expired coupon returns error', function (): void {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['variants'])->firstOrFail();
    $variant = $product->variants->firstOrFail();

    Coupon::create([
        'name' => 'Expired Deal',
        'coupon' => 'OLDDEAL',
        'type' => AmountOff::class,
        'starts_at' => now()->subMonths(2),
        'ends_at' => now()->subDay(),
        'data' => [
            'percentage' => 15,
            'fixed_value' => false,
        ],
    ]);

    $this->actingAs($user)
        ->post(route('cart.store', $product), [
            'variant_id' => $variant->id,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('cart.coupon.apply'), [
            'coupon_code' => 'OLDDEAL',
        ])
        ->assertSessionHasErrors(['coupon_code' => 'This coupon has expired.']);
});

test('applying a coupon before starts_at returns error', function (): void {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['variants'])->firstOrFail();
    $variant = $product->variants->firstOrFail();

    Coupon::create([
        'name' => 'Future Deal',
        'coupon' => 'FUTURE20',
        'type' => AmountOff::class,
        'starts_at' => now()->addDays(5),
        'data' => [
            'percentage' => 20,
            'fixed_value' => false,
        ],
    ]);

    $this->actingAs($user)
        ->post(route('cart.store', $product), [
            'variant_id' => $variant->id,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('cart.coupon.apply'), [
            'coupon_code' => 'FUTURE20',
        ])
        ->assertSessionHasErrors(['coupon_code' => 'This coupon is not active yet.']);
});

test('applying a coupon that exceeded max_uses returns error', function (): void {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['variants'])->firstOrFail();
    $variant = $product->variants->firstOrFail();

    Coupon::create([
        'name' => 'Limited Deal',
        'coupon' => 'LIMITED',
        'type' => AmountOff::class,
        'starts_at' => now()->subDay(),
        'max_uses' => 1,
        'uses' => 1,
        'data' => [
            'percentage' => 20,
            'fixed_value' => false,
        ],
    ]);

    $this->actingAs($user)
        ->post(route('cart.store', $product), [
            'variant_id' => $variant->id,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('cart.coupon.apply'), [
            'coupon_code' => 'LIMITED',
        ])
        ->assertSessionHasErrors(['coupon_code' => 'This coupon has reached its maximum redemption limit.']);
});

test('applying a coupon with minimum spend requirement fails when cart is below threshold', function (): void {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['variants'])->firstOrFail();
    $variant = $product->variants->firstOrFail();

    Coupon::create([
        'name' => 'High Roller',
        'coupon' => 'VIPONLY',
        'type' => AmountOff::class,
        'starts_at' => now()->subDay(),
        'data' => [
            'percentage' => 30,
            'fixed_value' => false,
            'min_prices' => ['ETB' => 100000000], // 1,000,000 ETB
        ],
    ]);

    $this->actingAs($user)
        ->post(route('cart.store', $product), [
            'variant_id' => $variant->id,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('cart.coupon.apply'), [
            'coupon_code' => 'VIPONLY',
        ])
        ->assertSessionHasErrors(['coupon_code']);
});

test('user can remove an applied coupon and cart returns to full price', function (): void {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['variants'])->firstOrFail();
    $variant = $product->variants->firstOrFail();

    Coupon::create([
        'name' => '10% Off',
        'coupon' => 'REMOVE10',
        'type' => AmountOff::class,
        'starts_at' => now()->subDay(),
        'data' => [
            'percentage' => 10,
            'fixed_value' => false,
        ],
    ]);

    $this->actingAs($user)
        ->post(route('cart.store', $product), [
            'variant_id' => $variant->id,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('cart.coupon.apply'), [
            'coupon_code' => 'REMOVE10',
        ])
        ->assertRedirect();

    expect(CartSession::current()->coupon_code)->toBe('REMOVE10');

    $this->actingAs($user)
        ->delete(route('cart.coupon.remove'))
        ->assertRedirect(route('cart.index'))
        ->assertSessionHas('status', 'Coupon removed from your cart.');

    $cart = CartSession::current();
    expect($cart->coupon_code)->toBeNull()
        ->and($cart->discountTotal?->value ?? 0)->toBe(0);
});

test('mini cart json endpoint returns discount information', function (): void {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['variants'])->firstOrFail();
    $variant = $product->variants->firstOrFail();

    Coupon::create([
        'name' => '10% Off Mini',
        'coupon' => 'MINI10',
        'type' => AmountOff::class,
        'starts_at' => now()->subDay(),
        'data' => [
            'percentage' => 10,
            'fixed_value' => false,
        ],
    ]);

    $this->actingAs($user)
        ->post(route('cart.store', $product), [
            'variant_id' => $variant->id,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->postJson(route('cart.coupon.apply'), [
            'coupon_code' => 'MINI10',
        ])
        ->assertOk()
        ->assertJsonPath('coupon_code', 'MINI10')
        ->assertJsonStructure([
            'message',
            'cart_count',
            'items',
            'total',
            'subtotal',
            'discount_total',
            'coupon_code',
        ]);
});

test('Chapa checkout charges the discounted total after coupon is applied', function (): void {
    $user = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['variants'])->firstOrFail();
    $variant = $product->variants->firstOrFail();

    Coupon::create([
        'name' => '20% Chapa Promo',
        'coupon' => 'CHAPA20',
        'type' => AmountOff::class,
        'starts_at' => now()->subDay(),
        'data' => [
            'percentage' => 20,
            'fixed_value' => false,
        ],
    ]);

    $this->actingAs($user)
        ->post(route('cart.store', $product), [
            'variant_id' => $variant->id,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('cart.coupon.apply'), [
            'coupon_code' => 'CHAPA20',
        ])
        ->assertRedirect();

    $cart = CartSession::current();
    $expectedTotal = number_format($cart->total->value / 100, 2, '.', '');

    Http::fake([
        'https://api.chapa.co/v1/transaction/initialize' => Http::response([
            'status' => 'success',
            'data' => ['checkout_url' => 'https://checkout.chapa.co/test-payment'],
        ]),
    ]);

    $this->actingAs($user)
        ->post(route('checkout.store'))
        ->assertRedirect('https://checkout.chapa.co/test-payment');

    Http::assertSent(function ($request) use ($expectedTotal) {
        return $request['amount'] === $expectedTotal;
    });
});

test('mini cart endpoint returns valid json for empty cart without errors', function (): void {
    $response = $this->getJson(route('cart.mini'));

    $response->assertSuccessful()
        ->assertJson([
            'cart_count' => 0,
            'items' => [],
            'total' => '0.00 ETB',
            'subtotal' => '0.00 ETB',
            'discount_total' => null,
            'coupon_code' => null,
        ]);
});
