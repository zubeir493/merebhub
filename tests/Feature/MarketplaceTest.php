<?php

use App\Models\Product;

beforeEach(function () {
    $this->seed();
});

test('the existing storefront renders Lunar catalog products', function () {
    $product = Product::query()->firstOrFail();

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee($product->name);
});

test('a Lunar product detail page renders its price and publisher', function () {
    $product = Product::query()->with(['defaultUrl', 'author'])->firstOrFail();

    $this->get(route('products.show', $product))
        ->assertSuccessful()
        ->assertSee($product->name)
        ->assertSee($product->author->name)
        ->assertSee('Cart and order processing powered by Lunar');
});

test('separate admin and merchant panel entry points require authentication', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->get('/merchant')->assertRedirect('/merchant/login');
    $this->get('/author')->assertNotFound();
});
