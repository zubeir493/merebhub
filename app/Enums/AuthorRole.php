<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AuthorRole: string implements HasLabel
{
    case PrimaryDeveloper = 'primary_developer';
    case CoDeveloper = 'co_developer';
    case Publisher = 'publisher';
    case Designer = 'designer';
    case SupportProvider = 'support_provider';
    case Contributor = 'contributor';

    public function getLabel(): string
    {
        return str($this->value)->headline();
    }
}
