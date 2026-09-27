<?php

namespace App\Filament\Admin\Widgets;

use Lunar\Filament\Widgets\Dashboard\Orders\OrdersSalesChart as BaseOrdersSalesChart;

class AdminOrdersSalesChart extends BaseOrdersSalesChart
{
    protected int|string|array $columnSpan = ['lg' => 1, 'xl' => 1];
}
