<?php

use App\Models\Product;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed();
});

test('the existing storefront renders Lunar catalog products', function () {
    $product = Product::published()->firstOrFail();

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee($product->name);
});

test('a Lunar product detail page renders its price and publisher', function () {
    $product = Product::published()->with(['defaultUrl', 'author'])->firstOrFail();

    $this->get(route('products.show', $product))
        ->assertSuccessful()
        ->assertSee($product->name)
        ->assertSee($product->author->name)
        ->assertSee('Cart and order processing powered by Lunar');
});

test('new products receive readable storefront slugs', function (): void {
    $product = Product::factory()->create([
        'name' => collect(['en' => 'A New Windows Utility']),
    ]);

    expect($product->fresh()->defaultUrl?->slug)->toBe('a-new-windows-utility')
        ->and($product->fresh()->getRouteKey())->toBe(Str::slug('A New Windows Utility'));
});

test('staff and merchant panel entry points require authentication', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->get('/merchant')->assertRedirect('/merchant/login');
    $this->get('/author')->assertNotFound();
});
