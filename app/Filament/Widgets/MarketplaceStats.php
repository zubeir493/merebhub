<?php

namespace App\Filament\Widgets;

use App\Enums\AppSubmissionStatus;
use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Models\AppSubmission;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MarketplaceStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            Stat::make('Paid revenue', number_format((float) Order::where('status', OrderStatus::Paid)->sum('amount')).' ETB')
                ->description('All completed orders')
                ->color('success'),
            Stat::make('Published software', Product::published()->count())
                ->description('Visible in the storefront')
                ->color('primary'),
            Stat::make('Pending reviews', AppSubmission::where('status', AppSubmissionStatus::Pending)->count())
                ->description('Submissions awaiting action')
                ->color('warning'),
            Stat::make('Active licenses', License::where('status', LicenseStatus::Active)->count())
                ->description('Managed through Keygen')
                ->color('info'),
        ];
    }
}
