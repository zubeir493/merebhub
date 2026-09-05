<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use App\Domain\Catalog\Actions\ReviewProductAction;
use App\Domain\Catalog\Enums\ProductPublicationState;
use App\Filament\Admin\Resources\Products\ProductResource;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Lunar\Core\Models\Staff;

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
                TextColumn::make('merchant.display_name')
                    ->label('Merchant')
                    ->searchable()
                    ->sortable(),
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
                TextColumn::make('source_type')
                    ->label('Source')
                    ->formatStateUsing(fn (mixed $state): string => str($state instanceof BackedEnum ? $state->value : $state)->replace('_', ' ')->title()),
                TextColumn::make('status')
                    ->label('Catalog status')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => str($state instanceof BackedEnum ? $state->value : $state)->replace('_', ' ')->title()),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Last updated'),
            ])
            ->filters([
                SelectFilter::make('publication_state')
                    ->options(ProductResource::publicationStateOptions()),
                SelectFilter::make('source_type')
                    ->options([
                        'local_developer' => 'Local developer',
                        'global_partner' => 'Global partner',
                    ]),
                SelectFilter::make('merchant_id')
                    ->relationship('merchant', 'display_name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->form([
                        Select::make('state')
                            ->label('Decision')
                            ->options([
                                ProductPublicationState::UnderReview->value => 'Under review',
                                ProductPublicationState::Approved->value => 'Approved',
                                ProductPublicationState::ChangesRequested->value => 'Changes requested',
                                ProductPublicationState::Rejected->value => 'Rejected',
                                ProductPublicationState::Published->value => 'Published',
                                ProductPublicationState::Archived->value => 'Archived',
                            ])
                            ->required(),
                        Textarea::make('reason')
                            ->label('Review note')
                            ->required()
                            ->maxLength(2000),
                    ])
                    ->action(function (Product $record, array $data, ReviewProductAction $review): void {
                        $reviewer = auth('staff')->user();

                        abort_unless($reviewer instanceof Staff, 403);

                        $review->handle(
                            $record,
                            $reviewer,
                            ProductPublicationState::from($data['state']),
                            $data['reason'],
                        );
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
