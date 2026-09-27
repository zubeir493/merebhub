<?php

test('unknown storefront paths render the branded not found page', function () {
    config()->set('app.debug', false);

    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertSee('This page isn’t here.')
        ->assertSee('Browse marketplace')
        ->assertSee('Back home');
});
