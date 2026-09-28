<?php

use App\Support\PublicMediaUrl;
use Illuminate\Support\Facades\Storage;

test('public media paths use temporary URLs', function (): void {
    Storage::fake('s3');

    $url = PublicMediaUrl::forPath('s3', 'gallery/example.png');

    expect($url)
        ->toContain('/gallery/example.png')
        ->toContain('expiration=');
});
