<?php

namespace App\Filament\Merchant\Pages;

use App\Filament\Merchant\Widgets\MerchantRecentSales;
use App\Filament\Merchant\Widgets\MerchantSalesChart;
use App\Filament\Merchant\Widgets\MerchantStatsOverview;
use App\Filament\Merchant\Widgets\MerchantTopProducts;
use App\Filament\Merchant\Widgets\MerchantWelcome;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Merchant overview';

    protected static ?string $navigationLabel = 'Overview';

    public function getWidgets(): array
    {
        return [
            MerchantWelcome::class,
            MerchantStatsOverview::class,
            MerchantSalesChart::class,
            MerchantTopProducts::class,
            MerchantRecentSales::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'sm' => 1,
            'lg' => 3,
        ];
    }
}
