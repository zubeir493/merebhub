<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    public static function fromMajor(int|string $amount): int
    {
        $normalized = trim((string) $amount);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new InvalidArgumentException('Invalid monetary amount.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public static function toMajor(int $minor): string
    {
        return number_format($minor / 100, 2, '.', '');
    }
}
