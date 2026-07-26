<?php

namespace App\Filament\Resources\Payments;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Pages\ManagePayments;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider_reference')->label('Reference')->searchable()->weight('bold'),
                TextColumn::make('order.public_id')->label('Order')->searchable()->limit(14),
                TextColumn::make('amount_minor')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state, Payment $record): string => number_format($state / 100, 2).' '.$record->currency),
                TextColumn::make('status')->badge(),
                TextColumn::make('verified_at')->dateTime()->placeholder('Pending'),
            ])
            ->filters([
                SelectFilter::make('status')->options(PaymentStatus::class),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePayments::route('/'),
        ];
    }
}
