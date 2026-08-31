<?php

namespace App\Models;

use App\Domain\Merchants\Enums\MerchantMembershipRole;
use App\Domain\Merchants\Enums\MerchantMembershipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantMembership extends Model
{
    use HasFactory;

    protected $table = 'merchant_users';

    protected $fillable = [
        'merchant_id',
        'user_id',
        'merchant_role',
        'status',
        'invited_at',
        'joined_at',
    ];

    protected $attributes = [
        'merchant_role' => MerchantMembershipRole::Owner->value,
        'status' => MerchantMembershipStatus::Invited->value,
    ];

    protected function casts(): array
    {
        return [
            'merchant_role' => MerchantMembershipRole::class,
            'status' => MerchantMembershipStatus::class,
            'invited_at' => 'datetime',
            'joined_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
