<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'public_id',
        'customer_id',
        'product_id',
        'product_plan_id',
        'order_item_id',
        'license_id',
        'previous_subscription_id',
        'status',
        'starts_at',
        'expires_at',
        'expired_at',
        'last_reminded_at',
        'last_reminder_days',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'expired_at' => 'datetime',
            'last_reminded_at' => 'datetime',
            'last_reminder_days' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productPlan(): BelongsTo
    {
        return $this->belongsTo(ProductPlan::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function previousSubscription(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_subscription_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(self::class, 'previous_subscription_id');
    }
}
