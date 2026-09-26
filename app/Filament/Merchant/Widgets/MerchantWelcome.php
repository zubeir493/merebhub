<?php

namespace App\Filament\Merchant\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class MerchantWelcome extends Widget
{
    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.merchant.widgets.welcome';

    protected function getViewData(): array
    {
        return [
            'user' => Auth::user(),
        ];
    }

    public function getGreeting(): string
    {
        return match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };
    }
}
