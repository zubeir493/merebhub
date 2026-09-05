<?php

namespace App\Providers;

use App\Filament\Admin\Resources\Products\ProductResource as AdminProductResource;
use App\Integrations\Chapa\ChapaPayment;
use App\Support\DashboardExtension;
use Filament\Navigation\NavigationBuilder;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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
                ->colors(['primary' => Color::Teal])
                ->font('Instrument Sans')
                ->path('admin')
                ->discoverResources(
                    in: app_path('Filament/Admin/Resources'),
                    for: 'App\\Filament\\Admin\\Resources',
                )
                ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                    $items = [];

                    foreach ([...LunarPanel::getPages(), ...LunarPanel::getActiveResources(), AdminProductResource::class] as $component) {
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

        Model::preventLazyLoading(! app()->isProduction());
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower($request->string('email')).'|'.$request->ip()));

        RateLimiter::for('public-form', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        View::composer('layouts.storefront', fn ($view) => $view->with([
            'headerCartCount' => CartSession::current()?->lines->sum('quantity') ?? 0,
        ]));
    }
}
