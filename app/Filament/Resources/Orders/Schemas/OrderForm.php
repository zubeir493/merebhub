<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('public_id')->disabled(),
                TextInput::make('transaction_reference')->disabled(),
                TextInput::make('buyer_email')
                    ->email()
                    ->required(),
                TextInput::make('buyer_user_id')
                    ->numeric(),
                TextInput::make('total_minor')->label('Total (minor units)')->disabled(),
                TextInput::make('currency')
                    ->required()
                    ->default('ETB'),
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('paid_at'),
            ]);
    }
}
