<?php

namespace App\Models;

use Filament\Models\Contracts\HasName;

class Staff extends \Lunar\Core\Models\Staff implements HasName
{
    public function getFilamentName(): string
    {
        return $this->full_name;
    }
}
