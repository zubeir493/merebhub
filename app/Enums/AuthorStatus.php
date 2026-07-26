<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AuthorStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Suspended = 'suspended';
    case Hidden = 'hidden';

    public function getLabel(): string
    {
        return str($this->value)->headline();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::PendingApproval => 'warning',
            self::Suspended => 'danger',
            self::Draft, self::Hidden => 'gray',
        };
    }
}
