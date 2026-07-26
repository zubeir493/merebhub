<?php

namespace App\Filament\Author\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Payouts extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Payouts';

    protected string $view = 'filament.author.pages.payouts';
}
