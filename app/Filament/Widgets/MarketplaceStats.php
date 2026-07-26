<?php

namespace App\Filament\Widgets;

use App\Enums\AppSubmissionStatus;
use App\Models\AppSubmission;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MarketplaceStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            Stat::make('Pending reviews', AppSubmission::where('status', AppSubmissionStatus::Pending)->count())
                ->description('Submissions awaiting action')
                ->color('warning'),
            Stat::make('Approved', AppSubmission::where('status', AppSubmissionStatus::Approved)->count())
                ->description('Ready for manual WooCommerce setup')
                ->color('success'),
            Stat::make('Rejected', AppSubmission::where('status', AppSubmissionStatus::Rejected)->count())
                ->description('Closed submissions')
                ->color('gray'),
        ];
    }
}
