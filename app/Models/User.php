<?php

namespace App\Models;

use App\Domain\Merchants\Enums\MerchantMembershipStatus;
use App\Domain\Merchants\Enums\MerchantStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Lunar\Core\Contracts\LunarUser as LunarUserInterface;
use Lunar\Core\Models\Concerns\IsLunarUser;

#[Fillable(['name', 'email', 'password', 'merchant_access'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, LunarUserInterface, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, IsLunarUser, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'merchant'
            && (bool) ($this->getAttribute('merchant_access') ?? false)
            && $this->approvedMerchants()->exists();
    }

    public function merchants(): BelongsToMany
    {
        return $this->belongsToMany(Merchant::class, 'merchant_users')
            ->withPivot(['merchant_role', 'status', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    public function activeMerchants(): BelongsToMany
    {
        return $this->merchants()->wherePivot('status', MerchantMembershipStatus::Active->value);
    }

    public function approvedMerchants(): BelongsToMany
    {
        return $this->activeMerchants()
            ->where((new Merchant)->qualifyColumn('status'), MerchantStatus::Approved->value);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'merchant_access' => 'boolean',
        ];
    }

    public function sendEmailVerificationNotification(): void
    {
        if ($this->shouldLogEmailVerificationLink()) {
            logger()->info('Email verification link (copy this URL): '.$this->emailVerificationUrl());
        }

        $this->notify(new VerifyEmail);
    }

    public function emailVerificationUrl(): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ],
        );
    }

    public function shouldLogEmailVerificationLink(): bool
    {
        return ! app()->isProduction() && config('mail.default') === 'log';
    }
}
