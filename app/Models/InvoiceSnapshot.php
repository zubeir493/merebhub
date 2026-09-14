<?php

namespace App\Models;

use Database\Factories\InvoiceSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Lunar\Core\Models\Order;

class InvoiceSnapshot extends Model
{
    /** @use HasFactory<InvoiceSnapshotFactory> */
    use HasFactory;

    protected $hidden = ['billing_snapshot', 'line_items'];

    protected $fillable = [
        'public_id',
        'user_id',
        'order_id',
        'invoice_number',
        'currency_code',
        'currency_factor',
        'currency_decimal_places',
        'payment_status',
        'subtotal',
        'discount_total',
        'tax_total',
        'shipping_total',
        'total',
        'billing_snapshot',
        'line_items',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'billing_snapshot' => 'encrypted:array',
            'line_items' => 'array',
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (InvoiceSnapshot $snapshot): void {
            $snapshot->public_id ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
