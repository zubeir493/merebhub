<?php

use App\Models\Product;

test('store collection routes render', function (string $routeName, string $heading) {
    $this->get(route($routeName))
        ->assertSuccessful()
        ->assertSee($heading)
        ->assertSee('Store collections');
})->with([
    'all software' => ['store.index', 'All software'],
    'new arrivals' => ['store.newarrivals', 'New arrivals'],
    'best sellers' => ['store.bestsellers', 'Best sellers'],
    'deals' => ['store.deals', 'Deals'],
]);

test('store search and category filters show only matching published products', function () {
    Product::factory()->published()->create([
        'name' => 'Soko Inventory',
        'category' => 'Business',
    ]);
    Product::factory()->published()->create([
        'name' => 'Netsa Studio',
        'category' => 'Design',
    ]);
    Product::factory()->create([
        'name' => 'Draft Inventory',
        'category' => 'Business',
    ]);

    $this->get(route('store.index', ['q' => 'Inventory', 'category' => 'Business']))
        ->assertSuccessful()
        ->assertSee('Soko Inventory')
        ->assertDontSee('Netsa Studio')
        ->assertDontSee('Draft Inventory');
});

test('new arrivals and best sellers use their collection ordering', function () {
    Product::factory()->published()->create([
        'name' => 'Older Popular App',
        'weekly_sales' => 900,
        'created_at' => now()->subWeek(),
    ]);
    Product::factory()->published()->create([
        'name' => 'Newer Quiet App',
        'weekly_sales' => 10,
        'created_at' => now(),
    ]);

    $this->get(route('store.newarrivals'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Newer Quiet App', 'Older Popular App']);

    $this->get(route('store.bestsellers'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Older Popular App', 'Newer Quiet App']);
});

test('deals list only discounted published products', function () {
    Product::factory()->published()->create([
        'name' => 'Discounted App',
        'price' => 1000,
        'compare_at_price' => 1500,
    ]);
    Product::factory()->published()->create([
        'name' => 'Full Price App',
        'price' => 1000,
        'compare_at_price' => null,
    ]);

    $this->get(route('store.deals'))
        ->assertSuccessful()
        ->assertSee('Discounted App')
        ->assertDontSee('Full Price App');
});
