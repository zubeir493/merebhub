<?php

use App\Models\Product;

beforeEach(function () {
    $this->seed();
});

test('store collection routes render', function (string $route, string $heading) {
    $this->get(route($route))->assertSuccessful()->assertSee($heading);
})->with([
    ['store.index', 'All software'],
    ['store.newarrivals', 'New arrivals'],
    ['store.bestsellers', 'Best sellers'],
    ['store.deals', 'Deals'],
]);

test('store search filters Lunar attribute data', function () {
    $this->get(route('store.index', ['q' => 'Ledgerly']))
        ->assertSuccessful()
        ->assertSee('1 product')
        ->assertSee('Ledgerly')
        ->assertDontSee('DeployMate');
});

test('draft products are not publicly routable or addable to the cart', function () {
    $draft = Product::query()
        ->where('publication_state', 'draft')
        ->with(['defaultUrl', 'variants'])
        ->firstOrFail();

    $this->get(route('products.show', $draft))
        ->assertNotFound();

    $this->post(route('cart.store', $draft), [
        'variant_id' => $draft->variants->first()->getKey(),
    ])->assertNotFound();
});
