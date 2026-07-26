<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SubscriptionStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Expired = 'expired';
    case Renewed = 'renewed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return str($this->value)->headline();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Expired => 'danger',
            self::Renewed => 'info',
            self::Cancelled => 'gray',
        };
    }
}
