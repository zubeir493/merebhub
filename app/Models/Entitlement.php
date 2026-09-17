<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Lunar\Core\Models\Order;
use Lunar\Core\Models\OrderLine;
use Lunar\Core\Models\ProductVariant;

class Entitlement extends Model
{
    protected $fillable = [
        'public_id',
        'user_id',
        'order_id',
        'order_line_id',
        'product_id',
        'fulfillment_unit_id',
        'type',
        'status',
        'provider',
        'external_id',
        'meta',
    ];

    protected $attributes = [
        'type' => 'license',
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(function (Entitlement $entitlement): void {
            $entitlement->public_id ??= (string) Str::ulid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fulfillmentUnit(): BelongsTo
    {
        return $this->belongsTo(FulfillmentUnit::class);
    }

    public function credential(): HasOne
    {
        return $this->hasOne(Credential::class);
    }

    public function variantDisplayName(): string
    {
        $variant = $this->orderLine?->purchasable;
        if ($variant instanceof ProductVariant) {
            return Product::displayVariantName($variant);
        }

        return trim((string) ($this->orderLine?->option ?: data_get($this->fulfillmentUnit?->meta, 'variant_name'))) ?: 'Standard license';
    }
}
