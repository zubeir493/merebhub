<?php

namespace App\Models;

use Database\Factories\StaffFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends \Lunar\Core\Models\Staff implements FilamentUser, HasName, HasAppAuthentication
{
    use InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery;

    protected static function newFactory(): StaffFactory
    {
        return StaffFactory::new();
    }

    public function assignedSupportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_staff_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'lunar' && $this->admin;
    }

    public function getFilamentName(): string
    {
        return $this->full_name;
    }
}
