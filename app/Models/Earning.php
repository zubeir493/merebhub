<?php

namespace App\Models;

use Database\Factories\EarningFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Earning extends Model
{
    /** @use HasFactory<EarningFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'author_id',
        'product_id',
        'currency',
        'gross_minor',
        'discount_minor',
        'net_minor',
        'platform_share_minor',
        'author_share_minor',
        'refund_deduction_minor',
        'final_author_earnings_minor',
        'status',
        'earned_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_minor' => 'integer',
            'discount_minor' => 'integer',
            'net_minor' => 'integer',
            'platform_share_minor' => 'integer',
            'author_share_minor' => 'integer',
            'refund_deduction_minor' => 'integer',
            'final_author_earnings_minor' => 'integer',
            'earned_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
