<?php

namespace App\Filament\Author\Resources\Earnings;

use App\Filament\Author\Resources\Earnings\Pages\ManageEarnings;
use App\Models\Earning;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EarningResource extends Resource
{
    protected static ?string $model = Earning::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereBelongsTo(auth()->user()->authorProfile, 'author');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('earned_at')->date()->sortable(),
                TextColumn::make('product.name')->searchable()->sortable(),
                TextColumn::make('order.public_id')->label('Order')->limit(14),
                TextColumn::make('gross_minor')
                    ->label('Gross')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2).' ETB'),
                TextColumn::make('final_author_earnings_minor')
                    ->label('Your earnings')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2).' ETB')
                    ->weight('bold'),
                TextColumn::make('status')->badge(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEarnings::route('/'),
        ];
    }
}
