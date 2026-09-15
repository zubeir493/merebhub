<?php

namespace App\Models;

use App\Domain\Fulfillment\Enums\FulfillmentUnitStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Lunar\Core\Models\Order;
use Lunar\Core\Models\OrderLine;
use Lunar\Core\Models\ProductVariant;

class FulfillmentUnit extends Model
{
    protected $fillable = [
        'public_id',
        'order_id',
        'order_line_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'type',
        'provider',
        'status',
        'idempotency_key',
        'external_id',
        'last_error',
        'meta',
        'completed_at',
    ];

    protected $attributes = [
        'status' => FulfillmentUnitStatus::Pending->value,
        'quantity' => 1,
        'type' => 'license',
        'provider' => 'fake-keygen',
    ];

    protected function casts(): array
    {
        return [
            'status' => FulfillmentUnitStatus::class,
            'meta' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FulfillmentUnit $unit): void {
            $unit->public_id ??= (string) Str::ulid();
        });
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

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(FulfillmentAttempt::class);
    }

    public function entitlement(): HasOne
    {
        return $this->hasOne(Entitlement::class);
    }

    public function providerMirror(): HasOne
    {
        return $this->hasOne(ProviderMirror::class);
    }
}
