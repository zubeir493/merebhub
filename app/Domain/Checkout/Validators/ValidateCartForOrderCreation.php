<?php

namespace App\Domain\Checkout\Validators;

use Lunar\Core\Models\CartLine;
use Lunar\Core\Validation\BaseValidator;

class ValidateCartForOrderCreation extends BaseValidator
{
    public function validate(): bool
    {
        $cart = $this->parameters['cart'];

        if ($cart->completedOrder) {
            return $this->fail('cart', __('lunar::exceptions.carts.order_exists'));
        }

        $unavailableLines = $cart->lines->filter(
            fn (CartLine $line): bool => ! $line->purchasable || ! $line->purchasable->isPurchasable()
        );

        if ($unavailableLines->isNotEmpty()) {
            return $this->fail('cart', __('lunar::exceptions.carts.line_unavailable', [
                'identifier' => $unavailableLines->map(
                    fn (CartLine $line): string => $line->purchasable?->getIdentifier() ?? "#{$line->id}"
                )->implode(', '),
            ]));
        }

        if ($cart->isShippable()) {
            return $this->fail('cart', 'Shipping is not supported for digital products.');
        }

        return $this->pass();
    }
}
