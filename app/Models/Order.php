<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'public_id',
        'transaction_reference',
        'buyer_email',
        'buyer_user_id',
        'product_id',
        'amount',
        'subtotal_minor',
        'discount_minor',
        'total_minor',
        'currency',
        'status',
        'paid_at',
        'payment_failed_at',
        'cancelled_at',
        'fulfillment_error',
        'payment_url',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'subtotal_minor' => 'integer',
            'discount_minor' => 'integer',
            'total_minor' => 'integer',
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
            'payment_failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function license(): HasOne
    {
        return $this->hasOne(License::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }
}
