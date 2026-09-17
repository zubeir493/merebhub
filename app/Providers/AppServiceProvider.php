<?php

namespace App\Providers;

use App\Domain\Fulfillment\Contracts\LicenseProvider;
use App\Domain\Fulfillment\Providers\FakeKeygenLicenseProvider;
use App\Domain\Fulfillment\Providers\KeygenLicenseProvider;
use App\Filament\Admin\Pages\KeygenLicenses;
use App\Filament\Admin\Pages\KeygenPolicies;
use App\Filament\Admin\Pages\KeygenProducts;
use App\Filament\Admin\Resources\Products\ProductResource as AdminProductResource;
use App\Filament\AvatarProviders\PrimaryColorAvatarProvider;
use App\Filament\Pages\Auth\EditProfile;
use App\Integrations\Chapa\ChapaPayment;
use App\Integrations\Keygen\KeygenClient;
use App\Models\BillingProfile;
use App\Models\Credential;
use App\Models\DownloadableAsset;
use App\Models\InvoiceSnapshot;
use App\Models\LicenseMapping;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\UserSession;
use App\Policies\BillingProfilePolicy;
use App\Policies\CredentialPolicy;
use App\Policies\DownloadableAssetPolicy;
use App\Policies\InvoiceSnapshotPolicy;
use App\Policies\LicenseMappingPolicy;
use App\Policies\SupportTicketAttachmentPolicy;
use App\Policies\SupportTicketPolicy;
use App\Policies\UserSessionPolicy;
use App\Support\DashboardExtension;
use Filament\Enums\ThemeMode;
use Filament\Navigation\NavigationBuilder;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Lunar\Admin\Filament\Pages\Dashboard;
use Lunar\Admin\Filament\Resources\ActivityResource;
use Lunar\Admin\Filament\Resources\ChannelResource;
use Lunar\Admin\Filament\Resources\CurrencyResource;
use Lunar\Admin\Filament\Resources\CustomerGroupResource;
use Lunar\Admin\Filament\Resources\LanguageResource;
use Lunar\Admin\Filament\Resources\LocationResource;
use Lunar\Admin\Filament\Resources\ProductOptionResource;
use Lunar\Admin\Filament\Resources\ProductResource as LunarProductResource;
use Lunar\Admin\Filament\Resources\ProductTypeResource;
use Lunar\Admin\Filament\Resources\RegionResource;
use Lunar\Admin\Filament\Resources\TaxClassResource;
use Lunar\Admin\Filament\Resources\TaxRateResource;
use Lunar\Admin\Filament\Resources\TaxZoneResource;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Core\Facades\CartSession;
use Lunar\Core\Facades\Payments;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LicenseProvider::class, function (): LicenseProvider {
            return match (config('marketplace.fulfillment.license_provider')) {
                'fake' => new FakeKeygenLicenseProvider,
                'keygen' => new KeygenLicenseProvider($this->app->make(KeygenClient::class)),
                default => throw new \LogicException('Unsupported license provider configured.'),
            };
        });

        LunarPanel::excludeResources([
            ActivityResource::class,
            ChannelResource::class,
            CurrencyResource::class,
            CustomerGroupResource::class,
            LanguageResource::class,
            LocationResource::class,
            ProductOptionResource::class,
            ProductTypeResource::class,
            RegionResource::class,
            TaxClassResource::class,
            TaxRateResource::class,
            TaxZoneResource::class,
            LunarProductResource::class,
        ])->withoutInventoryControls()
            ->panel(fn (Panel $panel): Panel => $panel
                ->brandName('MerebHub')
                ->viteTheme('resources/css/filament/admin/theme.css')
                ->font('Plus Jakarta Sans')
                ->darkMode(false)
                ->defaultThemeMode(ThemeMode::Light)
                ->spa()
                ->topbar()
                ->profile(EditProfile::class)
                ->databaseNotifications()
                ->defaultAvatarProvider(PrimaryColorAvatarProvider::class)
                ->brandLogo(asset('images/marketplace/logo.svg'))
                ->favicon(asset('favicon.ico'))
                ->brandLogoHeight('2rem')
                ->colors(['primary' => Color::Indigo])
                ->globalSearch(true)
                ->path('admin')
                ->pages([
                    KeygenProducts::class,
                    KeygenPolicies::class,
                    KeygenLicenses::class,
                ])
                ->discoverResources(
                    in: app_path('Filament/Admin/Resources'),
                    for: 'App\\Filament\\Admin\\Resources',
                )
                ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                    $items = [];

                    foreach ([
                        ...LunarPanel::getPages(),
                        KeygenProducts::class,
                        KeygenPolicies::class,
                        KeygenLicenses::class,
                        ...LunarPanel::getActiveResources(),
                        AdminProductResource::class,
                    ] as $component) {
                        $items = [...$items, ...$component::getNavigationItems()];
                    }

                    return $builder->items($items);
                })
            )
            ->extensions([
                Dashboard::class => new DashboardExtension,
            ])
            ->register();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Payments::extend('chapa', fn ($app): ChapaPayment => $app->make(ChapaPayment::class));

        Gate::policy(DownloadableAsset::class, DownloadableAssetPolicy::class);
        Gate::policy(Credential::class, CredentialPolicy::class);
        Gate::policy(BillingProfile::class, BillingProfilePolicy::class);
        Gate::policy(InvoiceSnapshot::class, InvoiceSnapshotPolicy::class);
        Gate::policy(LicenseMapping::class, LicenseMappingPolicy::class);
        Gate::policy(UserSession::class, UserSessionPolicy::class);
        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);
        Gate::policy(SupportTicketAttachment::class, SupportTicketAttachmentPolicy::class);

        Model::preventLazyLoading(! app()->isProduction());
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower($request->string('email')).'|'.$request->ip()));

        RateLimiter::for('public-form', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('credential-reveal', fn (Request $request) => Limit::perMinute(6)
            ->by($request->user()->getAuthIdentifier().'|'.$request->ip()));

        RateLimiter::for('support-ticket', fn (Request $request) => Limit::perMinute(5)
            ->by(($request->user()?->getAuthIdentifier() ?? 'guest').'|'.$request->ip()));

        RateLimiter::for('support-reply', fn (Request $request) => Limit::perMinute(10)
            ->by(($request->user()?->getAuthIdentifier() ?? $request->user('staff')?->getAuthIdentifier() ?? 'guest').'|'.$request->ip()));

        RateLimiter::for('session-revoke', fn (Request $request) => Limit::perMinute(10)
            ->by($request->user()->getAuthIdentifier().'|'.$request->ip()));

        View::composer('layouts.storefront', fn ($view) => $view->with([
            'headerCartCount' => CartSession::current()?->lines->sum('quantity') ?? 0,
        ]));
    }
}
