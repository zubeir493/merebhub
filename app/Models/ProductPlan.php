<?php

namespace App\Models;

use App\Enums\BillingInterval;
use App\Enums\BillingModel;
use App\Enums\FulfillmentType;
use Database\Factories\ProductPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPlan extends Model
{
    /** @use HasFactory<ProductPlanFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'slug',
        'description',
        'price_minor',
        'currency',
        'billing_model',
        'billing_interval',
        'license_type',
        'license_duration_days',
        'activation_limit',
        'entitlements',
        'support_duration_days',
        'update_duration_days',
        'download_limit',
        'keygen_policy_id',
        'fulfillment_type',
        'is_active',
        'sort_order',
    ];

    protected $attributes = [
        'currency' => 'ETB',
        'billing_model' => BillingModel::OneTime,
        'license_type' => 'perpetual',
        'activation_limit' => 1,
        'fulfillment_type' => FulfillmentType::LicenseKey,
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'billing_model' => BillingModel::class,
            'billing_interval' => BillingInterval::class,
            'license_duration_days' => 'integer',
            'activation_limit' => 'integer',
            'entitlements' => 'array',
            'support_duration_days' => 'integer',
            'update_duration_days' => 'integer',
            'download_limit' => 'integer',
            'fulfillment_type' => FulfillmentType::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
