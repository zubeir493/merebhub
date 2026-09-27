<?php

namespace App\Filament\Admin\Widgets;

use Lunar\Filament\Widgets\Dashboard\Orders\OrderStatsOverview as BaseOrderStatsOverview;

class AdminOrderStatsOverview extends BaseOrderStatsOverview
{
    protected int|string|array $columnSpan = 'full';
}
