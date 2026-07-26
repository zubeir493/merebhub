<?php

namespace App\Providers;

use App\Contracts\LicensingProvider;
use App\Contracts\PaymentGateway;
use App\Payments\ChapaPaymentGateway;
use App\Services\KeygenService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, ChapaPaymentGateway::class);
        $this->app->bind(LicensingProvider::class, KeygenService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower($request->string('email')).'|'.$request->ip()));

        RateLimiter::for('public-form', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));

        View::composer('layouts.storefront', function ($view): void {
            $user = auth()->user();

            $view->with([
                'headerCartCount' => $user?->cartItems()->count() ?? 0,
                'headerWishlistCount' => $user?->wishlistItems()->count() ?? 0,
            ]);
        });
    }
}
