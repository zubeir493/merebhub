<?php

namespace App\Filament\Admin\Resources\Coupons\Tables;

use App\Models\Coupon;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('coupon')
                    ->label('Code')
                    ->badge()
                    ->copyable()
                    ->copyMessage('Coupon code copied')
                    ->description(fn (Coupon $record): string => $record->name)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('discount_value')
                    ->label('Discount')
                    ->badge()
                    ->color('success')
                    ->state(fn (Coupon $record): string => $record->formattedDiscount())
                    ->description(fn (Coupon $record): ?string => $record->minimumSpend() ? 'Min: '.$record->formattedMinimumSpend() : null),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Coupon::ACTIVE => 'success',
                        Coupon::SCHEDULED => 'warning',
                        Coupon::EXPIRED => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('usage_display')
                    ->label('Usage')
                    ->state(fn (Coupon $record): string => $record->usage_display)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('uses', $direction)),
                TextColumn::make('ends_at')
                    ->label('Expires')
                    ->state(fn (Coupon $record): string => $record->ends_at ? $record->ends_at->format('M j, Y') : 'Never')
                    ->description(fn (Coupon $record): string => 'From '.($record->starts_at ? $record->starts_at->format('M j, Y') : 'now'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'scheduled' => 'Scheduled',
                        'expired' => 'Expired',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'active' => $query->where('starts_at', '<=', now())
                                ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now())),
                            'scheduled' => $query->where('starts_at', '>', now()),
                            'expired' => $query->whereNotNull('ends_at')->where('ends_at', '<=', now()),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->label('Actions')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->tooltip('Coupon actions')
                    ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
