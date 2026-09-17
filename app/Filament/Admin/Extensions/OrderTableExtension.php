<?php

namespace App\Filament\Admin\Extensions;

use App\Domain\Fulfillment\Actions\RegenerateOrderLicensesAction;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lunar\Core\Models\Order;
use Lunar\Core\Models\ProductVariant;
use Throwable;

class OrderTableExtension
{
    public function configureTable(Table $table): Table
    {
        return $table->recordActions([
            ...$table->getRecordActions(),
            Action::make('regenerateLicenses')
                ->label('Regenerate missing licenses')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (Order $record): bool => $record->placed_at !== null)
                ->action(function (Order $record, RegenerateOrderLicensesAction $regenerate): void {
                    try {
                        $result = $regenerate->handle($record);
                    } catch (Throwable $exception) {
                        report($exception);
                        Notification::make()
                            ->title('License regeneration failed')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    if ($result['failed'] !== []) {
                        Notification::make()
                            ->title('Some licenses still need attention')
                            ->body(sprintf(
                                '%d attempted, %d generated, %d failed.',
                                $result['attempted'],
                                $result['succeeded'],
                                count($result['failed']),
                            ))
                            ->warning()
                            ->send();

                        return;
                    }

                    if ($result['attempted'] === 0) {
                        Notification::make()
                            ->title('No missing licenses found')
                            ->info()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Missing licenses regenerated')
                        ->body(sprintf('%d license(s) generated successfully.', $result['succeeded']))
                        ->success()
                        ->send();
                }),
        ]);
    }

    /**
     * @param  array<int, mixed>  $columns
     * @return array<int, mixed>
     */
    public function extendOrderLinesTableColumns(array $columns): array
    {
        return [
            ...$columns,
            TextColumn::make('variant_name')
                ->label('Variant')
                ->placeholder('Standard license')
                ->getStateUsing(function ($record): string {
                    $variant = $record->purchasable;

                    return $variant instanceof ProductVariant
                        ? Product::displayVariantName($variant)
                        : ($record->option ?: 'Standard license');
                }),
        ];
    }
}
