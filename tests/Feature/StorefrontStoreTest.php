<?php

use App\Models\Author;
use App\Models\Product;

beforeEach(function () {
    $this->seed();
});

test('store collection routes render', function (string $route, string $heading) {
    $this->get(route($route))->assertSuccessful()->assertSee($heading);
})->with([
    ['store.index', 'Software worth using.'],
    ['store.newarrivals', 'Fresh tools, thoughtfully made.'],
    ['store.bestsellers', 'What customers keep choosing.'],
    ['store.deals', 'Good tools, better prices.'],
]);

test('store search filters Lunar attribute data', function () {
    $this->get(route('store.index', ['q' => 'Ledgerly']))
        ->assertSuccessful()
        ->assertSee('“Ledgerly”')
        ->assertSee('1 result')
        ->assertSee('Filters')
        ->assertSee('Ledgerly')
        ->assertDontSee('DeployMate')
        ->assertDontSee('Store collections');
});

test('catalog uses the global search and asynchronous filter controls', function () {
    $this->get(route('store.index'))
        ->assertSuccessful()
        ->assertSee('Search software, makers, categories')
        ->assertSee('data-catalog-filter-form', false)
        ->assertSee('data-catalog-sort-form', false)
        ->assertDontSee('desktop-catalog-search', false)
        ->assertDontSee('Clear all')
        ->assertDontSee('>Apply<', false);
});

test('legacy search redirects to the unified store catalog', function () {
    $this->get(route('search', ['q' => 'Ledgerly']))
        ->assertRedirect(route('store.index', ['q' => 'Ledgerly']));
});

test('unified catalog keeps matching developers in search results', function () {
    $author = Author::query()->where('name', 'Soko Labs')->firstOrFail();

    $this->get(route('store.index', ['q' => 'Soko']))
        ->assertSuccessful()
        ->assertSee('Developers matching “Soko”')
        ->assertSee($author->name);
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
