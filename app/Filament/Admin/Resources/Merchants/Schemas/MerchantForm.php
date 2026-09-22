<?php

namespace App\Filament\Admin\Resources\Merchants\Schemas;

use App\Domain\Merchants\Enums\MerchantStatus;
use App\Domain\Merchants\Enums\MerchantType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MerchantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Merchant profile')
                    ->description('Public identity and business details for this software vendor.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('display_name')
                            ->label('Display name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('legal_name')
                            ->label('Legal business name')
                            ->maxLength(255)
                            ->nullable(),
                        TextInput::make('slug')
                            ->label('Storefront slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Select::make('type')
                            ->label('Merchant type')
                            ->options([
                                MerchantType::LocalDeveloper->value => 'Local Developer',
                                MerchantType::GlobalPartner->value => 'Global Partner',
                            ])
                            ->required(),
                        Select::make('status')
                            ->label('Account status')
                            ->options([
                                MerchantStatus::Pending->value => 'Pending review',
                                MerchantStatus::Approved->value => 'Approved',
                                MerchantStatus::Suspended->value => 'Suspended',
                                MerchantStatus::Rejected->value => 'Rejected',
                            ])
                            ->default(MerchantStatus::Pending->value)
                            ->required(),
                        TextInput::make('website_url')
                            ->label('Website URL')
                            ->url()
                            ->maxLength(255)
                            ->nullable(),
                        DateTimePicker::make('approved_at')
                            ->label('Approval date')
                            ->nullable(),
                    ]),
            ]);
    }
}
