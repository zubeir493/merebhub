<?php

namespace App\Pipelines\Order\Creation;

use Closure;
use Lunar\Core\Models\Order;

class CreateOrderLines extends \Lunar\Core\Pipelines\Order\Creation\CreateOrderLines
{
    /**
     * Ensure variant options remain available after Lunar refreshes the cart.
     */
    public function handle(Order $order, Closure $next): mixed
    {
        $order->loadMissing('cart.lines.purchasable.values');

        return parent::handle($order, $next);
    }
}
