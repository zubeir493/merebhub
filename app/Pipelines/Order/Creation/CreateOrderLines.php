<?php

namespace App\Pipelines\Order\Creation;

use App\Models\Product;
use Closure;
use Lunar\Core\Models\Order;
use Lunar\Core\Models\ProductVariant;

class CreateOrderLines extends \Lunar\Core\Pipelines\Order\Creation\CreateOrderLines
{
    /**
     * Ensure variant options remain available after Lunar refreshes the cart.
     */
    public function handle(Order $order, Closure $next): mixed
    {
        $order->loadMissing('cart.lines.purchasable.values');

        return parent::handle($order, function (Order $createdOrder) use ($next): mixed {
            $createdOrder->loadMissing('lines.purchasable.product');

            $createdOrder->lines->each(function ($line): void {
                $variant = $line->purchasable;
                if (! $variant instanceof ProductVariant) {
                    return;
                }

                $line->forceFill([
                    'option' => Product::displayVariantName($variant),
                ])->save();
            });

            return $next($createdOrder->refresh());
        });
    }
}
