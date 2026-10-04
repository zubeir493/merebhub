<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use Lunar\Core\Models\Cart;

beforeEach(function () {
    $this->seed();
});

test('the existing storefront renders Lunar catalog products', function () {
    $product = Product::published()->firstOrFail();

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee($product->name)
        ->assertSee('Software that earns its place in your day.', false)
        ->assertSee('Popular with shoppers')
        ->assertSee('Useful software, made closer to home.')
        ->assertSee(route('store.index'));
});

test('storefront categories resolve their configured outline icon', function (): void {
    $category = Category::query()->where('name', 'Business')->firstOrFail();

    expect($category->icon)->toBe('briefcase')
        ->and($category->iconComponent())->toBe('heroicon-o-briefcase');
});

test('a Lunar product detail page renders its price and publisher', function () {
    $product = Product::published()->with(['defaultUrl', 'author'])->firstOrFail();

    $this->get(route('products.show', $product))
        ->assertSuccessful()
        ->assertSee($product->name)
        ->assertSee($product->author->name)
        ->assertSee('Secure Br checkout by Chapa');
});

test('new products receive readable storefront slugs', function (): void {
    $product = Product::factory()->create([
        'name' => collect(['en' => 'A New Windows Utility']),
    ]);

    expect($product->fresh()->defaultUrl?->slug)->toBe('a-new-windows-utility')
        ->and($product->fresh()->getRouteKey())->toBe(Str::slug('A New Windows Utility'));
});

test('public storefront pages do not create empty carts', function (): void {
    $cartCount = Cart::query()->count();

    $this->get(route('home'))->assertSuccessful();

    expect(Cart::query()->count())->toBe($cartCount);
});

test('staff and merchant panel entry points require authentication', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->get('/merchant')->assertRedirect('/merchant/login');
    $this->get('/author')->assertNotFound();
});
