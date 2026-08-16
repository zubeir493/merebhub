<?php

use App\Models\Author;

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

    $this->get(route('vendors.show', $author))
        ->assertSuccessful()
        ->assertSee($author->name)
        ->assertSee($author->products->first()->name);
});
