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
        'marketplace_license_id',
        'customer_id',
        'product_id',
        'product_plan_id',
        'buyer_email',
        'provider',
        'provider_product_id',
        'provider_policy_id',
        'provider_license_id',
        'keygen_license_id',
        'license_key',
        'status',
        'activation_limit',
        'activation_count',
        'issued_at',
        'expires_at',
        'suspended_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LicenseStatus::class,
            'license_key' => 'encrypted',
            'activation_limit' => 'integer',
            'activation_count' => 'integer',
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'suspended_at' => 'datetime',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function productPlan(): BelongsTo
    {
        return $this->belongsTo(ProductPlan::class);
    }
}
