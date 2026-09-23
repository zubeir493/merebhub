<?php

namespace App\Filament\Admin\Resources\Coupons\Schemas;

use App\Models\Coupon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Coupon details')
                    ->description('Set promotional code, discount type, and percentage or fixed value.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('coupon')
                            ->label('Coupon code')
                            ->placeholder('e.g. MEREB20')
                            ->required()
                            ->maxLength(50)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? Str::upper(trim($state)) : null)
                            ->unique(
                                table: (new Coupon)->getTable(),
                                column: 'coupon',
                                ignoreRecord: true
                            )
                            ->suffixAction(
                                Action::make('generateCode')
                                    ->icon(Heroicon::Sparkles)
                                    ->tooltip('Generate random code')
                                    ->action(function (callable $set): void {
                                        $set('coupon', 'MEREB-'.Str::upper(Str::random(6)));
                                    })
                            )
                            ->helperText('Customers enter this code at checkout.'),
                        TextInput::make('name')
                            ->label('Title / internal name')
                            ->placeholder('e.g. 20% Off Launch Special')
                            ->required()
                            ->maxLength(255),
                        Select::make('discount_type')
                            ->label('Discount type')
                            ->options([
                                'percentage' => 'Percentage discount (%)',
                                'fixed_amount' => 'Fixed amount discount (ETB)',
                            ])
                            ->default('percentage')
                            ->required()
                            ->live(),
                        TextInput::make('discount_value')
                            ->label(fn (callable $get): string => $get('discount_type') === 'fixed_amount' ? 'Discount amount (ETB)' : 'Discount percentage (%)')
                            ->numeric()
                            ->prefix(fn (callable $get): ?string => $get('discount_type') === 'fixed_amount' ? 'ETB' : null)
                            ->suffix(fn (callable $get): ?string => $get('discount_type') === 'percentage' ? '%' : null)
                            ->minValue(0.01)
                            ->maxValue(fn (callable $get): ?float => $get('discount_type') === 'percentage' ? 100 : null)
                            ->required()
                            ->helperText(fn (callable $get): string => $get('discount_type') === 'fixed_amount'
                                ? 'Flat amount in Ethiopian Birr deducted from the cart subtotal.'
                                : 'Percentage deducted from eligible cart lines (1–100%).'),
                    ]),

                Section::make('Usage restrictions & conditions')
                    ->description('Define minimum cart value and redemption limits.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('minimum_spend')
                            ->label('Minimum spend (ETB)')
                            ->numeric()
                            ->prefix('ETB')
                            ->nullable()
                            ->helperText('Minimum cart subtotal required to use code. Leave blank for no minimum.'),
                        TextInput::make('max_uses')
                            ->label('Total usage limit')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->nullable()
                            ->helperText('Max total redemptions across all users. Leave blank for unlimited.'),
                        TextInput::make('max_uses_per_user')
                            ->label('Limit per customer')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->default(1)
                            ->nullable()
                            ->helperText('Max times a single customer can redeem. Default is 1.'),
                    ]),

                Section::make('Active period')
                    ->description('Configure schedule and expiration date.')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Starts at')
                            ->default(now())
                            ->required(),
                        DateTimePicker::make('ends_at')
                            ->label('Expires at')
                            ->nullable()
                            ->after('starts_at')
                            ->helperText('Leave blank if the coupon should never expire.'),
                    ]),
            ]);
    }
}
