<?php

namespace App\Support;

use App\Models\IntegrationSetting;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MailtrapConfigurator
{
    public function __construct(private readonly MailManager $mailManager) {}

    public function apply(): void
    {
        try {
            if (! Schema::hasTable((new IntegrationSetting)->getTable())) {
                return;
            }

            $settings = IntegrationSetting::query()
                ->where('provider', 'mailtrap')
                ->get()
                ->mapWithKeys(fn (IntegrationSetting $setting): array => [$setting->key => $setting->value]);
        } catch (Throwable) {
            return;
        }

        if (! filter_var($settings->get('enabled', false), FILTER_VALIDATE_BOOL)) {
            config()->set('mail.default', config('mail.fallback_default', 'log'));
            $this->mailManager->purge('mailtrap');

            return;
        }

        config()->set([
            'mail.default' => 'mailtrap',
            'mail.mailers.mailtrap.host' => $settings->get('host', config('mail.mailers.mailtrap.host')),
            'mail.mailers.mailtrap.port' => (int) $settings->get('port', config('mail.mailers.mailtrap.port', 2525)),
            'mail.mailers.mailtrap.username' => $settings->get('username', config('mail.mailers.mailtrap.username')),
            'mail.mailers.mailtrap.password' => $settings->get('password', config('mail.mailers.mailtrap.password')),
            'mail.mailers.mailtrap.scheme' => $settings->get('scheme', config('mail.mailers.mailtrap.scheme')),
            'mail.from.address' => $settings->get('from_address', config('mail.from.address')),
            'mail.from.name' => $settings->get('from_name', config('mail.from.name')),
        ]);

        $this->mailManager->purge('mailtrap');
    }
}
