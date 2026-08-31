<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;

class Staff extends \Lunar\Core\Models\Staff implements FilamentUser, HasName
{
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'lunar' && $this->admin;
    }

    public function getFilamentName(): string
    {
        return $this->full_name;
    }
}
