<?php

namespace App\Providers;

use App\Domain\Fulfillment\Contracts\LicenseProvider;
use App\Domain\Fulfillment\Providers\FakeKeygenLicenseProvider;
use App\Domain\Fulfillment\Providers\KeygenLicenseProvider;
use App\Filament\Admin\Extensions\OrderTableExtension;
use App\Filament\Admin\Pages\IntegrationSettings;
use App\Filament\Admin\Pages\KeygenLicenses;
use App\Filament\Admin\Pages\KeygenPolicies;
use App\Filament\Admin\Pages\KeygenProducts;
use App\Filament\Admin\Resources\Categories\CategoryResource;
use App\Filament\Admin\Resources\FulfillmentUnits\FulfillmentUnitResource;
use App\Filament\Admin\Resources\LicenseMappings\LicenseMappingResource;
use App\Filament\Admin\Resources\Merchants\MerchantResource;
use App\Filament\Admin\Resources\Products\ProductResource as AdminProductResource;
use App\Filament\Admin\Resources\SupportTickets\SupportTicketResource;
use App\Filament\AvatarProviders\PrimaryColorAvatarProvider;
use App\Filament\Pages\Auth\EditProfile;
use App\Integrations\Chapa\ChapaPayment;
use App\Integrations\Keygen\KeygenClient;
use App\Models\BillingProfile;
use App\Models\Credential;
use App\Models\DownloadableAsset;
use App\Models\InvoiceSnapshot;
use App\Models\LicenseMapping;
use App\Models\Staff;
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
use App\Support\MailtrapConfigurator;
use App\Support\S3StorageConfigurator;
use Filament\Enums\ThemeMode;
use Filament\Navigation\NavigationBuilder;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Lunar\Admin\Filament\Pages\Dashboard;
use Lunar\Admin\Filament\Resources\ActivityResource;
use Lunar\Admin\Filament\Resources\AttributeGroupResource;
use Lunar\Admin\Filament\Resources\BrandResource;
use Lunar\Admin\Filament\Resources\ChannelResource;
use Lunar\Admin\Filament\Resources\CollectionGroupResource;
use Lunar\Admin\Filament\Resources\CurrencyResource;
use Lunar\Admin\Filament\Resources\CustomerGroupResource;
use Lunar\Admin\Filament\Resources\CustomerResource;
use Lunar\Admin\Filament\Resources\DiscountResource;
use Lunar\Admin\Filament\Resources\LanguageResource;
use Lunar\Admin\Filament\Resources\LocationResource;
use Lunar\Admin\Filament\Resources\OrderResource;
use Lunar\Admin\Filament\Resources\OrderResource\Pages\Components\OrderItemsTable;
use Lunar\Admin\Filament\Resources\ProductOptionResource;
use Lunar\Admin\Filament\Resources\ProductResource as LunarProductResource;
use Lunar\Admin\Filament\Resources\ProductTypeResource;
use Lunar\Admin\Filament\Resources\ProductVariantResource;
use Lunar\Admin\Filament\Resources\RegionResource;
use Lunar\Admin\Filament\Resources\StaffResource;
use Lunar\Admin\Filament\Resources\TagResource;
use Lunar\Admin\Filament\Resources\TaxClassResource;
use Lunar\Admin\Filament\Resources\TaxRateResource;
use Lunar\Admin\Filament\Resources\TaxZoneResource;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Core\Facades\CartSession;
use Lunar\Core\Facades\Payments;
use Lunar\Filament\Support\Facades\LunarFilament;
use Lunar\Filament\Tables\Order\OrderTable;

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

        $navigationSorts = [
            OrderResource::class => 10,
            BrandResource::class => 50,
            CollectionGroupResource::class => 40,
            TagResource::class => 60,
            CustomerResource::class => 70,
            DiscountResource::class => 90,
            StaffResource::class => 130,
        ];

        $navigationParents = [
            BrandResource::class => 'Products',
            CollectionGroupResource::class => 'Products',
            TagResource::class => 'Products',
        ];

        LunarPanel::excludeResources([
            ActivityResource::class,
            AttributeGroupResource::class,
            ChannelResource::class,
            CurrencyResource::class,
            CustomerGroupResource::class,
            LanguageResource::class,
            LocationResource::class,
            ProductOptionResource::class,
            ProductTypeResource::class,
            ProductVariantResource::class,
            RegionResource::class,
            TaxClassResource::class,
            TaxRateResource::class,
            TaxZoneResource::class,
            LunarProductResource::class,
        ])->withoutInventoryControls();

        foreach (LunarPanel::getActiveResources() as $resource) {
            $resource::navigationGroup(null);

            if (isset($navigationSorts[$resource])) {
                $resource::navigationSort($navigationSorts[$resource]);
            }

        }

        LunarPanel::panel(fn (Panel $panel): Panel => $panel
            ->brandName('MerebHub')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->font('Plus Jakarta Sans')
            ->darkMode(false)
            ->defaultThemeMode(ThemeMode::Light)
            ->spa()
            ->topbar()
            ->sidebarCollapsibleOnDesktop(false)
            ->sidebarFullyCollapsibleOnDesktop(false)
            ->profile(EditProfile::class)
            ->databaseNotifications()
            ->defaultAvatarProvider(PrimaryColorAvatarProvider::class)
            ->brandLogo(null)
            ->favicon(asset('favicon.ico'))
            ->colors(['primary' => Color::Indigo])
            ->globalSearch(true)
            ->path('admin')
            ->pages([
                IntegrationSettings::class,
                KeygenProducts::class,
                KeygenPolicies::class,
                KeygenLicenses::class,
            ])
            ->discoverResources(
                in: app_path('Filament/Admin/Resources'),
                for: 'App\\Filament\\Admin\\Resources',
            )
            ->navigation(function (NavigationBuilder $builder) use ($navigationParents): NavigationBuilder {
                $items = [];

                foreach ([
                    ...LunarPanel::getPages(),
                    KeygenProducts::class,
                    KeygenPolicies::class,
                    KeygenLicenses::class,
                    ...LunarPanel::getActiveResources(),
                    AdminProductResource::class,
                    CategoryResource::class,
                    MerchantResource::class,
                    FulfillmentUnitResource::class,
                    LicenseMappingResource::class,
                    SupportTicketResource::class,
                    IntegrationSettings::class,
                ] as $component) {
                    if (method_exists($component, 'canAccess') && ! $component::canAccess()) {
                        continue;
                    }

                    foreach ($component::getNavigationItems() as $item) {
                        $item = $item->group(null);

                        if (isset($navigationParents[$component])) {
                            $item->parentItem($navigationParents[$component]);
                        }

                        $items[] = $item;
                    }
                }

                $items = collect($items)
                    ->filter(fn ($item): bool => $item->isVisible())
                    ->sortBy(fn ($item): int => $item->getSort())
                    ->values();

                $parentItems = $items->groupBy(fn ($item): string => $item->getParentItem() ?? '');
                $items = $parentItems->get('', collect());

                $parentItems->except([''])->each(function ($children, string $parentKey) use ($items): void {
                    $parent = $items->first(
                        fn ($item): bool => $item->getKey() === $parentKey || $item->getLabel() === $parentKey,
                    );

                    if (! $parent) {
                        return;
                    }

                    $parent->childItems(
                        collect($parent->getChildItems())
                            ->merge($children)
                            ->sortBy(fn ($item): int => $item->getSort())
                            ->values()
                            ->all(),
                    );
                });

                return $builder->items(
                    $items
                        ->filter(fn ($item): bool => filled($item->getChildItems()) || filled($item->getUrl()))
                        ->values()
                        ->all(),
                );
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
    public function boot(MailtrapConfigurator $mailtrap, S3StorageConfigurator $storage): void
    {
        config()->set('lunar.staff.model', Staff::class);
        config()->set('auth.providers.staff.model', Staff::class);

        if ($this->app->runningInConsole() && isset($_SERVER['argv'])) {
            $commands = array_slice($_SERVER['argv'], 1);
            if (array_intersect($commands, ['config:cache', 'optimize'])) {
                config()->set('lunar-filament.record_urls', []);
            }
        }

        $mailtrap->apply();
        $storage->apply();
        Queue::before(function () use ($mailtrap, $storage): void {
            $mailtrap->apply();
            $storage->apply();
        });

        FilamentIcon::register([
            'lunar::dashboard' => 'heroicon-o-home',
        ]);

        Payments::extend('chapa', fn ($app): ChapaPayment => $app->make(ChapaPayment::class));
        LunarFilament::extensions([
            OrderTable::class => new OrderTableExtension,
            OrderItemsTable::class => new OrderTableExtension,
        ]);

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
