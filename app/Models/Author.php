<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Lunar\Core\Models\Brand;

class Author extends Brand
{
    protected $table = 'brands';

    public function getMorphClass(): string
    {
        return 'brand';
    }

    protected function tagline(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->attr('tagline'));
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    protected function bio(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->attr('bio'));
    }

    protected function isVerified(): Attribute
    {
        return Attribute::get(fn (): bool => (bool) $this->attr('is_verified'));
    }

    protected function averageRating(): Attribute
    {
        return Attribute::get(fn (): float => (float) ($this->attr('average_rating') ?: 0));
    }

    protected function publicSalesCount(): Attribute
    {
        return Attribute::get(fn (): int => (int) ($this->attr('public_sales_count') ?: 0));
    }

    protected function showPublicSales(): Attribute
    {
        return Attribute::get(fn (): bool => true);
    }

    protected function websiteUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->attr('website_url'));
    }

    protected function supportUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->attr('support_url'));
    }

    protected function location(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->attr('location'));
    }

    protected function memberSince(): Attribute
    {
        return Attribute::get(fn () => $this->created_at);
    }

    public function avatarUrl(): ?string
    {
        return $this->mediaUrl('avatar_url');
    }

    public function coverUrl(): ?string
    {
        return $this->mediaUrl('cover_url');
    }

    public function getRouteKey(): mixed
    {
        return $this->defaultUrl?->slug ?? Str::slug($this->name);
    }

    private function mediaUrl(string $attribute): ?string
    {
        $path = (string) $this->attr($attribute);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, ['/images/', 'images/'])) {
            return asset(ltrim($path, '/'));
        }

        return Storage::disk('public')->url($path);
    }
}
