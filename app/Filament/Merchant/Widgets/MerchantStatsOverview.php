<?php

namespace App\Filament\Merchant\Widgets;

use App\Domain\Catalog\Enums\ProductPublicationState;
use App\Filament\Merchant\Widgets\Concerns\InteractsWithMerchantSales;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MerchantStatsOverview extends StatsOverviewWidget
{
    use InteractsWithMerchantSales;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $currentStart = now()->startOfMonth();
        $currentEnd = now()->endOfMonth();
        $previousStart = $currentStart->copy()->subMonth()->startOfMonth();
        $previousEnd = $currentStart->copy()->subMonth()->endOfMonth();

        $current = $this->summarizeSales($currentStart, $currentEnd);
        $previous = $this->summarizeSales($previousStart, $previousEnd);
        $currency = $this->merchantCurrencyCode();
        $averageOrderValue = $current['orders'] > 0 ? $current['sales'] / $current['orders'] : 0;
        $products = $this->merchantProductsQuery();
        $totalProducts = (clone $products)->count();
        $publishedProducts = (clone $products)
            ->where('publication_state', ProductPublicationState::Published->value)
            ->count();
        $needsAttention = (clone $products)
            ->whereIn('publication_state', [
                ProductPublicationState::ChangesRequested->value,
                ProductPublicationState::Rejected->value,
            ])
            ->count();

        return [
            Stat::make('Sales this month', $this->formatMinorAmount($current['sales'], $currency))
                ->description($this->comparisonDescription($current['sales'], $previous['sales'], 'last month'))
                ->descriptionIcon($this->comparisonIcon($current['sales'], $previous['sales']))
                ->color($this->comparisonColor($current['sales'], $previous['sales'])),
            Stat::make('Orders this month', number_format($current['orders']))
                ->description($this->comparisonDescription($current['orders'], $previous['orders'], 'last month'))
                ->descriptionIcon($this->comparisonIcon($current['orders'], $previous['orders']))
                ->color($this->comparisonColor($current['orders'], $previous['orders'])),
            Stat::make('Average order value', $this->formatMinorAmount($averageOrderValue, $currency))
                ->description('Sales divided by orders'),
            Stat::make('Items sold', number_format($current['items']))
                ->description('Across all placed orders'),
            Stat::make('Published products', number_format($publishedProducts))
                ->description(sprintf('%d total catalog products', $totalProducts))
                ->color('success'),
            Stat::make('Needs attention', number_format($needsAttention))
                ->description($needsAttention > 0 ? 'Products needing changes' : 'Your catalog is up to date')
                ->descriptionIcon($needsAttention > 0 ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedCheckCircle)
                ->color($needsAttention > 0 ? 'warning' : 'success'),
        ];
    }

    private function comparisonDescription(int $current, int $previous, string $period): string
    {
        if ($current === 0 && $previous === 0) {
            return 'No activity yet';
        }

        if ($previous === 0) {
            return 'New activity this month';
        }

        $change = (($current - $previous) / $previous) * 100;

        return sprintf('%s%.1f%% vs %s', $change >= 0 ? '+' : '', $change, $period);
    }

    private function comparisonIcon(int $current, int $previous): Heroicon
    {
        return $current >= $previous
            ? Heroicon::OutlinedArrowTrendingUp
            : Heroicon::OutlinedArrowTrendingDown;
    }

    private function comparisonColor(int $current, int $previous): string
    {
        if ($current === $previous) {
            return 'gray';
        }

        return $current > $previous ? 'success' : 'danger';
    }
}
