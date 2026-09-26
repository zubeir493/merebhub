<?php

namespace App\Filament\Admin\Resources\Merchants\Schemas;

use App\Domain\Merchants\Enums\MerchantMembershipRole;
use App\Domain\Merchants\Enums\MerchantType;
use App\Models\Merchant;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MerchantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('display_name')
                    ->label('Name')
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
                TextInput::make('website_url')
                    ->label('Website URL')
                    ->url()
                    ->maxLength(255)
                    ->nullable(),
                TextInput::make('account_email')
                    ->label('Email')
                    ->email()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->unique(
                        User::class,
                        'email',
                        fn (?Merchant $record): ?User => $record?->memberships()
                            ->where('merchant_role', MerchantMembershipRole::Owner->value)
                            ->first()?->user,
                    )
                    ->maxLength(255),
                TextInput::make('account_password')
                    ->label('Password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->autocomplete('new-password'),
            ]);
    }
}
