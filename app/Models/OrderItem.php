<?php

namespace App\Models;

use App\Enums\BillingModel;
use App\Enums\FulfillmentType;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_plan_id',
        'renewal_subscription_id',
        'quantity',
        'unit_amount',
        'total',
        'product_name',
        'plan_name',
        'unit_amount_minor',
        'discount_minor',
        'total_minor',
        'currency',
        'primary_author_snapshot',
        'commission_basis_points',
        'platform_share_minor',
        'author_share_minor',
        'billing_model',
        'fulfillment_type',
        'license_configuration',
        'support_duration_days',
        'update_duration_days',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'unit_amount_minor' => 'integer',
            'discount_minor' => 'integer',
            'total_minor' => 'integer',
            'primary_author_snapshot' => 'array',
            'commission_basis_points' => 'integer',
            'platform_share_minor' => 'integer',
            'author_share_minor' => 'integer',
            'license_configuration' => 'array',
            'billing_model' => BillingModel::class,
            'fulfillment_type' => FulfillmentType::class,
            'support_duration_days' => 'integer',
            'update_duration_days' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productPlan(): BelongsTo
    {
        return $this->belongsTo(ProductPlan::class);
    }

    public function renewalSubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'renewal_subscription_id');
    }

    public function license(): HasOne
    {
        return $this->hasOne(License::class);
    }
}
