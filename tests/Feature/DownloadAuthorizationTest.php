<?php

test('legacy licensed download endpoints are no longer exposed', function () {
    $this->get('/downloads/1/1')->assertNotFound();
});
