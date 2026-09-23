<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Lunar\Core\DiscountTypes\AmountOff;
use Lunar\Core\Models\Discount;

class Coupon extends Discount
{
    protected static function booted(): void
    {
        static::addGlobalScope('coupon', function (Builder $builder): void {
            $builder->whereNotNull('coupon')->where('coupon', '!=', '');
        });

        static::creating(function (Coupon $coupon): void {
            $coupon->type ??= AmountOff::class;
            $coupon->handle ??= 'coupon-'.Str::slug((string) $coupon->coupon).'-'.Str::lower(Str::random(4));
            $coupon->priority ??= 1;
            $coupon->stop ??= false;
        });

        static::deleting(function (Coupon $coupon): void {
            $coupon->customerGroups()->detach();
            $coupon->channels()->detach();
        });
    }

    public function getTable()
    {
        if ($this->table) {
            return $this->table;
        }

        return 'discounts';
    }

    public function getForeignKey(): string
    {
        return 'discount_id';
    }

    public function getMorphClass(): string
    {
        return (new Discount)->getMorphClass();
    }

    public function isPercentage(): bool
    {
        return ! (bool) ($this->data['fixed_value'] ?? false);
    }

    public function discountValue(): float
    {
        if ($this->isPercentage()) {
            return (float) ($this->data['percentage'] ?? 0);
        }

        return (float) (($this->data['fixed_values']['ETB'] ?? 0) / 100);
    }

    public function formattedDiscount(): string
    {
        if ($this->isPercentage()) {
            $val = rtrim(rtrim(number_format($this->discountValue(), 2, '.', ''), '0'), '.');

            return "{$val}% Off";
        }

        return number_format($this->discountValue(), 2, '.', ',').' ETB Off';
    }

    public function minimumSpend(): ?float
    {
        $min = $this->data['min_prices']['ETB'] ?? null;

        return $min !== null ? (float) ($min / 100) : null;
    }

    public function formattedMinimumSpend(): ?string
    {
        $spend = $this->minimumSpend();

        return $spend !== null ? number_format($spend, 2, '.', ',').' ETB' : null;
    }

    public function getUsageDisplayAttribute(): string
    {
        $max = $this->max_uses !== null ? (string) $this->max_uses : '∞';

        return "{$this->uses} / {$max}";
    }
}
