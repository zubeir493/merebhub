<?php

namespace App\Pricing;

use Lunar\Core\Pricing\DefaultPriceFormatter;
use NumberFormatter;

class BrPriceFormatter extends DefaultPriceFormatter
{
    protected function formatValue(
        int|float $value,
        ?string $locale = null,
        string $formatterStyle = NumberFormatter::CURRENCY,
        ?int $decimalPlaces = null,
        bool $trimTrailingZeros = true,
    ): mixed {
        $formattedPrice = parent::formatValue(
            $value,
            $locale,
            $formatterStyle,
            $decimalPlaces,
            $trimTrailingZeros,
        );

        if ($this->currency?->code !== 'ETB' || ! is_string($formattedPrice)) {
            return $formattedPrice;
        }

        return preg_replace('/ETB(?=\s|\x{00a0}|$)/u', 'Br', $formattedPrice) ?? $formattedPrice;
    }
}
