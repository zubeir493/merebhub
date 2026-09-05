<?php

namespace App\Models;

use App\Domain\Catalog\Enums\ProductPublicationState;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Lunar\Core\Facades\StorefrontSession;
use Lunar\Core\Models\Price;

class Product extends \Lunar\Core\Models\Product
{
    protected $fillable = [
        'attribute_data',
        'public_id',
        'product_type_id',
        'status',
        'brand_id',
        'name',
        'description',
        'short_description',
        'merchant_id',
        'source_type',
        'publication_state',
        'current_revision_id',
        'support_owner',
        'official_partner',
        'fulfillment_summary',
        'published_at',
        'archived_at',
    ];

    protected static function newFactory(): Factory
    {
        return ProductFactory::new();
    }

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

    protected function shortDescription(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedColumn('short_description'));
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

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function scopeForMerchant(Builder $query, Merchant $merchant): Builder
    {
        return $query->where('merchant_id', $merchant->getKey());
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
            ->where('publication_state', ProductPublicationState::Published->value)
            ->channel(StorefrontSession::getChannel())
            ->customerGroup(StorefrontSession::getCustomerGroups());
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $like = "%{$term}%";

        return $query->where(function (Builder $query) use ($like): void {
            foreach (['name', 'description', 'short_description', 'attribute_data'] as $column) {
                self::addTextContains($query, $column, $like, 'or');
            }

            $query->orWhereIn('brand_id', Author::query()
                ->where('name', 'like', $like)
                ->select('id'));
            $query->orWhereIn('merchant_id', Merchant::query()
                ->where('display_name', 'like', $like)
                ->select('id'));
        });
    }

    public function scopeCatalogAttributeContains(Builder $query, string $column, string $value): Builder
    {
        if (! in_array($column, ['attribute_data'], true)) {
            throw new InvalidArgumentException("The [{$column}] column is not searchable as catalog metadata.");
        }

        return $query->where(function (Builder $query) use ($column, $value): void {
            self::addTextContains($query, $column, "%{$value}%");
        });
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

    private static function addTextContains(Builder $query, string $column, string $value, string $boolean = 'and'): void
    {
        $wrappedColumn = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($column));
        $driver = $query->getModel()->getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            $query->whereRaw("CAST({$wrappedColumn} AS TEXT) ILIKE ?", [$value], $boolean);

            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $query->whereRaw("CAST({$wrappedColumn} AS CHAR) LIKE ?", [$value], $boolean);

            return;
        }

        $query->where($query->qualifyColumn($column), 'like', $value, $boolean);
    }
}
