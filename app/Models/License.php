<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use Database\Factories\LicenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    /** @use HasFactory<LicenseFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'product_id',
        'buyer_email',
        'keygen_license_id',
        'license_key',
        'status',
        'activation_limit',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LicenseStatus::class,
            'activation_limit' => 'integer',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
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

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
