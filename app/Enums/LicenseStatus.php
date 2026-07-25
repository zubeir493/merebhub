<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LicenseStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Revoked = 'revoked';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return str($this->value)->headline();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Revoked => 'danger',
            self::Expired => 'gray',
        };
    }
}
