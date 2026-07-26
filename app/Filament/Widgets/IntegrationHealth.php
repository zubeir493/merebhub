<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IntegrationHealth extends StatsOverviewWidget
{
    protected ?string $heading = 'Integration readiness';

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $chapaReady = filled(config('services.chapa.secret_key'))
            && filled(config('services.chapa.webhook_secret'));
        $keygenReady = filled(config('services.keygen.api_url'))
            && filled(config('services.keygen.api_token'))
            && filled(config('services.keygen.account_id'))
            && filled(config('services.keygen.policy_id'));
        $mailReady = config('mail.default') !== 'log';

        return [
            Stat::make('Chapa', $chapaReady ? 'Connected' : 'Needs configuration')
                ->description('Hosted checkout and payment verification')
                ->color($chapaReady ? 'success' : 'warning'),
            Stat::make('Keygen', $keygenReady ? 'Connected' : 'Needs configuration')
                ->description('License creation and revocation')
                ->color($keygenReady ? 'success' : 'warning'),
            Stat::make('Transactional mail', $mailReady ? 'Configured' : 'Using local log')
                ->description('Purchase and account emails')
                ->color($mailReady ? 'success' : 'gray'),
        ];
    }
}
