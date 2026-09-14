<?php

namespace App\Models;

use Database\Factories\BillingProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingProfile extends Model
{
    /** @use HasFactory<BillingProfileFactory> */
    use HasFactory;

    protected $hidden = [
        'company_name',
        'tax_identifier',
        'first_name',
        'last_name',
        'contact_email',
        'contact_phone',
        'line_one',
        'line_two',
        'city',
        'state',
        'postcode',
    ];

    protected $fillable = [
        'user_id',
        'billing_type',
        'company_name',
        'tax_identifier',
        'first_name',
        'last_name',
        'contact_email',
        'contact_phone',
        'line_one',
        'line_two',
        'city',
        'state',
        'postcode',
        'country_iso3',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'company_name' => 'encrypted',
            'tax_identifier' => 'encrypted',
            'first_name' => 'encrypted',
            'last_name' => 'encrypted',
            'contact_email' => 'encrypted',
            'contact_phone' => 'encrypted',
            'line_one' => 'encrypted',
            'line_two' => 'encrypted',
            'city' => 'encrypted',
            'state' => 'encrypted',
            'postcode' => 'encrypted',
        ];
    }
}
