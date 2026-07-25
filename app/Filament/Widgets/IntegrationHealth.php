<?php

namespace App\Filament\Widgets;

use App\Services\WooCommerceService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IntegrationHealth extends StatsOverviewWidget
{
    protected ?string $heading = 'Integration readiness';

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $wooReady = app(WooCommerceService::class)->isConfigured();
        $keygenReady = filled(config('services.keygen.api_url'))
            && filled(config('services.keygen.api_token'))
            && filled(config('services.keygen.account_id'))
            && filled(config('services.keygen.policy_id'));
        $mailReady = config('mail.default') !== 'log';

        return [
            Stat::make('WooCommerce', $wooReady ? 'Connected' : 'Needs configuration')
                ->description('Catalog, checkout, and webhook')
                ->color($wooReady ? 'success' : 'warning'),
            Stat::make('Keygen', $keygenReady ? 'Connected' : 'Needs configuration')
                ->description('License creation and revocation')
                ->color($keygenReady ? 'success' : 'warning'),
            Stat::make('Transactional mail', $mailReady ? 'Configured' : 'Using local log')
                ->description('Purchase and account emails')
                ->color($mailReady ? 'success' : 'gray'),
        ];
    }
}
