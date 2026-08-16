<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockRemovedLunarPages
{
    /**
     * @var array<int, string>
     */
    private const BLOCKED_PATHS = [
        'lunar/activities*',
        'lunar/channels*',
        'lunar/currencies*',
        'lunar/customer-groups*',
        'lunar/languages*',
        'lunar/locations*',
        'lunar/product-options*',
        'lunar/product-types*',
        'lunar/regions*',
        'lunar/tax*',
        'lunar/products/*/shipping',
        'lunar/product-variants/*/shipping',
    ];

    private const BLOCKED_ROUTE_NAMES = [
        'filament.lunar.resources.products.shipping',
        'filament.lunar.resources.product-variants.shipping',
        'filament.lunar.taxes',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs(self::BLOCKED_ROUTE_NAMES)) {
            abort(404);
        }

        foreach (self::BLOCKED_PATHS as $pattern) {
            if ($request->is($pattern)) {
                abort(404);
            }
        }

        return $next($request);
    }
}
