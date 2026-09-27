<?php

namespace App\Support;

use App\Filament\Admin\Widgets\AdminLatestOrdersTable;
use App\Filament\Admin\Widgets\AdminOrdersSalesChart;
use App\Filament\Admin\Widgets\AdminOrderStatsOverview;
use App\Filament\Admin\Widgets\AdminPopularProductsTable;

class DashboardExtension
{
    public function getOverviewWidgets(array $widgets): array
    {
        return [AdminOrderStatsOverview::class];
    }

    public function getChartWidgets(array $widgets): array
    {
        return [AdminPopularProductsTable::class, AdminOrdersSalesChart::class];
    }

    public function getTableWidgets(array $widgets): array
    {
        return [AdminLatestOrdersTable::class];
    }
}
