<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Lunar\Core\Contracts\LunarUser as LunarUserInterface;
use Lunar\Core\Models\Concerns\IsLunarUser;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements LunarUserInterface, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, IsLunarUser, Notifiable;

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
