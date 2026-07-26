<?php

namespace App\Filament\Author\Widgets;

use App\Models\Author;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AuthorStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        /** @var Author|null $author */
        $author = auth()->user()?->authorProfile;

        if (! $author) {
            return [];
        }

        $earnings = $author->earnings();

        return [
            Stat::make('Gross sales', number_format((int) (clone $earnings)->sum('gross_minor') / 100, 2).' ETB')
                ->description((clone $earnings)->distinct('order_id')->count('order_id').' paid orders'),
            Stat::make('Author earnings', number_format((int) (clone $earnings)->sum('final_author_earnings_minor') / 100, 2).' ETB')
                ->color('success'),
            Stat::make('Pending earnings', number_format((int) (clone $earnings)->where('status', 'pending')->sum('final_author_earnings_minor') / 100, 2).' ETB')
                ->color('warning'),
            Stat::make('Products', $author->products()->count()),
        ];
    }
}
