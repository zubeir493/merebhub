<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Lunar\Core\Models\ProductVariant;

class LicenseMapping extends Model
{
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'keygen_product_id',
        'keygen_policy_id',
        'label',
        'active',
    ];

    protected $attributes = [
        'active' => true,
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public static function resolveFor(int $productId, ?int $productVariantId = null): ?self
    {
        if ($productVariantId !== null) {
            $variantMapping = static::query()
                ->active()
                ->where('product_id', $productId)
                ->where('product_variant_id', $productVariantId)
                ->first();

            if ($variantMapping !== null) {
                return $variantMapping;
            }
        }

        return static::query()
            ->active()
            ->where('product_id', $productId)
            ->whereNull('product_variant_id')
            ->first();
    }

    protected static function booted(): void
    {
        static::saving(function (LicenseMapping $mapping): void {
            $duplicate = static::query()
                ->where('product_id', $mapping->product_id)
                ->when(
                    $mapping->product_variant_id === null,
                    fn (Builder $query): Builder => $query->whereNull('product_variant_id'),
                    fn (Builder $query): Builder => $query->where('product_variant_id', $mapping->product_variant_id),
                )
                ->when($mapping->exists, fn (Builder $query): Builder => $query->whereKeyNot($mapping->getKey()))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'A license mapping already exists for this product and variant scope.',
                ]);
            }

            if ($mapping->product_variant_id === null) {
                return;
            }

            $variantProductId = ProductVariant::query()
                ->whereKey($mapping->product_variant_id)
                ->value('product_id');

            if ((int) $variantProductId !== (int) $mapping->product_id) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'The selected variant must belong to the selected product.',
                ]);
            }
        });
    }
}
