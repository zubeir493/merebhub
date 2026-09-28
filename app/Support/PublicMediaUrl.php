<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class PublicMediaUrl
{
    public static function forPath(string $disk, string $path): string
    {
        $storage = Storage::disk($disk);

        try {
            return $storage->temporaryUrl($path, self::expiration());
        } catch (Throwable) {
            return $storage->url($path);
        }
    }

    public static function forMedia(Media $media, string $conversion = ''): string
    {
        try {
            return $media->getTemporaryUrl(self::expiration(), $conversion);
        } catch (Throwable) {
            return $media->getUrl($conversion);
        }
    }

    private static function expiration(): DateTimeInterface
    {
        return now()
            ->addMinutes(max(1, (int) config('marketplace.public_media_url_expiry_minutes', 30)))
            ->endOfHour();
    }
}
