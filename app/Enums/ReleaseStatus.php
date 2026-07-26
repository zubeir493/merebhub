<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReleaseStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return str($this->value)->headline();
    }
}
