<?php

namespace App\Filament\Admin\Resources\Coupons\Pages;

use App\Filament\Admin\Resources\Coupons\CouponResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Lunar\Core\DiscountTypes\AmountOff;

class CreateCoupon extends CreateRecord
{
    protected static string $resource = CouponResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $isFixed = ($data['discount_type'] ?? 'percentage') === 'fixed_amount';
        $value = (float) ($data['discount_value'] ?? 0);
        $minSpend = isset($data['minimum_spend']) && filled($data['minimum_spend'])
            ? (int) round(((float) $data['minimum_spend']) * 100)
            : null;

        $discountData = [
            'fixed_value' => $isFixed,
            'percentage' => $isFixed ? 0 : $value,
            'fixed_values' => $isFixed ? ['ETB' => (int) round($value * 100)] : [],
            'min_prices' => $minSpend ? ['ETB' => $minSpend] : [],
        ];

        $data['data'] = $discountData;
        $data['type'] = AmountOff::class;
        $data['handle'] = 'coupon-'.Str::slug((string) $data['coupon']).'-'.Str::lower(Str::random(4));
        $data['priority'] = 1;
        $data['stop'] = false;

        unset($data['discount_type'], $data['discount_value'], $data['minimum_spend']);

        return $data;
    }
}
