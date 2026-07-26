<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'provider_reference',
        'provider_payment_id',
        'amount_minor',
        'currency',
        'status',
        'verification_payload',
        'verified_at',
        'failed_at',
    ];

    protected $attributes = [
        'provider' => 'chapa',
        'currency' => 'ETB',
        'status' => PaymentStatus::Pending,
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'status' => PaymentStatus::class,
            'verification_payload' => 'array',
            'verified_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
