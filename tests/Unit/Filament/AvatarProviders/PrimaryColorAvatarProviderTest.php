<?php

use App\Filament\AvatarProviders\PrimaryColorAvatarProvider;

test('it generates the gravatar URL expected by Lunar activity feeds', function (): void {
    $url = PrimaryColorAvatarProvider::generateGravatarUrl(' admin@example.com ', size: 200);

    expect($url)->toBe('https://www.gravatar.com/avatar/e64c7d89f26bd1972efa854d13d7dd61?d=mp&s=200');
});
