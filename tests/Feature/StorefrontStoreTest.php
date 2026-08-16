<?php

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
        ->assertSee('Ledgerly')
        ->assertDontSee('DeployMate');
});
