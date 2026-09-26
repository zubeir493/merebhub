<?php

namespace App\Filament\Admin\Resources\Merchants\Pages;

use App\Domain\Merchants\Actions\CreateMerchantAction;
use App\Domain\Merchants\Enums\MerchantStatus;
use App\Filament\Admin\Resources\Merchants\MerchantResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMerchant extends CreateRecord
{
    protected static string $resource = MerchantResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $accountData = [
            'name' => $data['display_name'],
            'email' => $data['account_email'],
            'password' => $data['account_password'],
        ];

        unset(
            $data['account_email'],
            $data['account_password'],
        );

        return app(CreateMerchantAction::class)->handleWithNewOwner(
            $accountData,
            [
                ...$data,
                'status' => MerchantStatus::Approved,
            ],
        );
    }
}
