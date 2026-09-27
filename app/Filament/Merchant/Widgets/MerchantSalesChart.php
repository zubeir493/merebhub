<?php

namespace App\Filament\Merchant\Widgets;

use App\Filament\Merchant\Widgets\Concerns\InteractsWithMerchantSales;
use Filament\Widgets\ChartWidget;

class MerchantSalesChart extends ChartWidget
{
    use InteractsWithMerchantSales;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = ['md' => 2, 'lg' => 2, 'xl' => 2];

    protected ?string $heading = 'Sales performance';

    protected function getData(): array
    {
        $start = now()->subDays(29)->startOfDay();
        $end = now()->endOfDay();
        $salesByDay = $this->merchantSalesJoinQuery()
            ->whereBetween('merchant_sales_orders.placed_at', [$start, $end])
            ->selectRaw('DATE(merchant_sales_orders.placed_at) as sale_date, COALESCE(SUM(merchant_sales_lines.total), 0) as sales')
            ->groupByRaw('DATE(merchant_sales_orders.placed_at)')
            ->pluck('sales', 'sale_date');

        $labels = [];
        $sales = [];

        for ($day = 0; $day < 30; $day++) {
            $date = $start->copy()->addDays($day);
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            $sales[] = (float) ($salesByDay[$key] ?? 0) / 100;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => $sales,
                    'borderColor' => '#4f46e5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.14)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
