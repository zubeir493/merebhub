<?php

use App\Models\Author;
use App\Models\Product;

beforeEach(function () {
    $this->seed();
});

test('Lunar brands power the developer directory', function () {
    $author = Author::query()->with('defaultUrl')->firstOrFail();

    $this->get(route('vendors.index'))
        ->assertSuccessful()
        ->assertSee($author->name);
});

test('a Lunar brand page lists its published products', function () {
    $author = Author::query()->with(['defaultUrl', 'products'])->firstOrFail();
    $product = Product::published()->whereBelongsTo($author, 'author')->firstOrFail();

    $this->get(route('vendors.show', $author))
        ->assertSuccessful()
        ->assertSee($author->name)
        ->assertSee($product->name);
});
