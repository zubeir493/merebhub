<?php

namespace App\Support;

use Lunar\Filament\Widgets\Dashboard\Orders\LatestOrdersTable;
use Lunar\Filament\Widgets\Dashboard\Orders\OrdersSalesChart;
use Lunar\Filament\Widgets\Dashboard\Orders\OrderStatsOverview;
use Lunar\Filament\Widgets\Dashboard\Orders\PopularProductsTable;

class DashboardExtension
{
    public function getOverviewWidgets(array $widgets): array
    {
        return [OrderStatsOverview::class];
    }

    public function getChartWidgets(array $widgets): array
    {
        return [OrdersSalesChart::class];
    }

    public function getTableWidgets(array $widgets): array
    {
        return [LatestOrdersTable::class, PopularProductsTable::class];
    }
}
