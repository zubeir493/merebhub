<?php

namespace App\Filament\Admin\Resources\Coupons\Pages;

use App\Filament\Admin\Resources\Coupons\CouponResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCoupon extends EditRecord
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $discountData = $this->record->data ?? [];
        $isFixed = (bool) ($discountData['fixed_value'] ?? false);

        $data['discount_type'] = $isFixed ? 'fixed_amount' : 'percentage';
        $data['discount_value'] = $isFixed
            ? (float) (($discountData['fixed_values']['ETB'] ?? 0) / 100)
            : (float) ($discountData['percentage'] ?? 0);

        $minPrices = $discountData['min_prices']['ETB'] ?? null;
        $data['minimum_spend'] = $minPrices !== null ? (float) ($minPrices / 100) : null;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
        unset($data['discount_type'], $data['discount_value'], $data['minimum_spend']);

        return $data;
    }
}
