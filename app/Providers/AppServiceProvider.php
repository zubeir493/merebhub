<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Core\Facades\CartSession;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        LunarPanel::register();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower($request->string('email')).'|'.$request->ip()));

        RateLimiter::for('public-form', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        View::composer('layouts.storefront', fn ($view) => $view->with([
            'headerCartCount' => CartSession::current()?->lines->sum('quantity') ?? 0,
        ]));
    }
}
