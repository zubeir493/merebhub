<?php

namespace App\Models;

use App\Domain\Merchants\Enums\MerchantMembershipStatus;
use App\Domain\Merchants\Enums\MerchantStatus;
use App\Domain\Merchants\Enums\MerchantType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'type',
        'legal_name',
        'display_name',
        'slug',
        'profile',
        'logo_path',
        'website_url',
        'status',
        'approved_at',
        'support_metadata',
    ];

    protected $attributes = [
        'status' => MerchantStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'type' => MerchantType::class,
            'status' => MerchantStatus::class,
            'approved_at' => 'datetime',
            'support_metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Merchant $merchant): void {
            $merchant->public_id ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'merchant_users')
            ->withPivot(['merchant_role', 'status', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(MerchantMembership::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(MerchantApplication::class);
    }

    public function privateProfile(): HasOne
    {
        return $this->hasOne(MerchantPrivateProfile::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->whereHas('memberships', function (Builder $membershipQuery) use ($user): void {
            $membershipQuery
                ->where('user_id', $user->getKey())
                ->where('status', MerchantMembershipStatus::Active->value);
        });
    }
}
