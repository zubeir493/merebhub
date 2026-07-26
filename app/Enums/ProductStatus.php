<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Published = 'published';
    case Suspended = 'suspended';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return str($this->value)->headline();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft, self::Archived => 'gray',
            self::Submitted, self::UnderReview => 'warning',
            self::ChangesRequested, self::Suspended => 'danger',
            self::Approved => 'info',
            self::Published => 'success',
        };
    }
}
