<?php

namespace App\Models;

use App\Domain\Merchants\Enums\MerchantApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'user_id',
        'submitted_data',
        'state',
        'assigned_vendor_manager_id',
        'decision_reason',
        'submitted_at',
        'reviewed_at',
    ];

    protected $attributes = [
        'state' => MerchantApplicationStatus::Submitted->value,
    ];

    protected function casts(): array
    {
        return [
            'submitted_data' => 'array',
            'state' => MerchantApplicationStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vendorManager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_vendor_manager_id');
    }
}
