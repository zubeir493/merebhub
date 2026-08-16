<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Lunar\Core\Facades\StorefrontSession;
use Lunar\Core\Models\Price;

class Product extends \Lunar\Core\Models\Product
{
    public function getMorphClass(): string
    {
        return 'product';
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedColumn('name'));
    }

    protected function tagline(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->attr('tagline'));
    }

    protected function description(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedColumn('description'));
    }

    protected function category(): Attribute
    {
        return Attribute::get(fn (): string => (string) ($this->attr('category') ?: 'Software'));
    }

    protected function rating(): Attribute
    {
        return Attribute::get(fn (): float => (float) ($this->attr('rating') ?: 0));
    }

    protected function ratingsCount(): Attribute
    {
        return Attribute::get(fn (): int => (int) ($this->attr('ratings_count') ?: 0));
    }

    protected function platforms(): Attribute
    {
        return Attribute::get(fn (): Collection => collect(explode(',', (string) $this->attr('platform')))
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->map(fn (string $name): object => (object) ['name' => $name, 'slug' => Str::slug($name)])
            ->values());
    }

    protected function price(): Attribute
    {
        return Attribute::get(fn (): float => (float) ($this->storefrontPrice()?->price ?? 0) / 100);
    }

    protected function compareAtPrice(): Attribute
    {
        return Attribute::get(fn (): ?float => ($price = $this->storefrontPrice()?->list_price) === null ? null : (float) $price / 100);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'brand_id');
    }

    public function coverUrl(): ?string
    {
        $mediaUrl = $this->getFirstMediaUrl(config('lunar.media.collection'));

        if ($mediaUrl !== '') {
            return $mediaUrl;
        }

        $path = (string) $this->attr('cover_url');

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

    public function getRouteKey(): mixed
    {
        return $this->defaultUrl?->slug ?? $this->getKey();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->channel(StorefrontSession::getChannel())
            ->customerGroup(StorefrontSession::getCustomerGroups());
    }

    private function storefrontPrice(): ?Price
    {
        $variant = $this->variants->first();

        if (! $variant) {
            return null;
        }

        return $variant->prices
            ->firstWhere('currency_id', StorefrontSession::getCurrency()?->id)
            ?? $variant->prices->first();
    }

    private function localizedColumn(string $column): string
    {
        $value = json_decode((string) $this->getRawOriginal($column), true);

        if (! is_array($value)) {
            return (string) $value;
        }

        return (string) ($value[app()->getLocale()] ?? reset($value) ?: '');
    }
}
