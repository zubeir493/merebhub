<?php

test('unknown storefront paths render the branded not found page', function () {
    config()->set('app.debug', false);

    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertSee('We couldn’t find that page.')
        ->assertSee('Browse the marketplace');
});
