<?php

namespace App\Filament\Merchant\Resources\Products\Tables;

use App\Domain\Catalog\Actions\SubmitProductForReviewAction;
use App\Domain\Catalog\Enums\ProductPublicationState;
use App\Filament\Merchant\Resources\Products\ProductResource;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('productType.name')
                    ->label('Type')
                    ->searchable(),
                TextColumn::make('publication_state')
                    ->label('Review state')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => str($state instanceof BackedEnum ? $state->value : $state)->replace('_', ' ')->title())
                    ->color(fn (string $state): string => match ($state) {
                        ProductPublicationState::Published->value => 'success',
                        ProductPublicationState::Submitted->value,
                        ProductPublicationState::UnderReview->value => 'warning',
                        ProductPublicationState::Rejected->value => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Catalog status')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => str($state instanceof BackedEnum ? $state->value : $state)->replace('_', ' ')->title()),
                TextColumn::make('updated_at')
                    ->label('Last updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('publication_state')
                    ->options(ProductResource::publicationStateOptions()),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('submit')
                    ->label('Submit for review')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (Product $record): bool => in_array($record->publication_state, [
                        ProductPublicationState::Draft->value,
                        ProductPublicationState::ChangesRequested->value,
                    ], true))
                    ->action(function (Product $record, SubmitProductForReviewAction $submit): void {
                        $submit->handle($record);
                    }),
            ]);
    }
}
