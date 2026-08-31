<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantPrivateProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'registration_number',
        'tax_identifier',
        'payout_account',
        'verification_status',
        'verified_at',
    ];

    protected $hidden = [
        'registration_number',
        'tax_identifier',
        'payout_account',
    ];

    protected function casts(): array
    {
        return [
            'registration_number' => 'encrypted',
            'tax_identifier' => 'encrypted',
            'payout_account' => 'encrypted',
            'verified_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
