<?php

namespace App\Filament\Admin\Resources\SupportTickets;

use App\Filament\Admin\Resources\SupportTickets\Pages\ManageSupportTickets;
use App\Models\SupportTicket;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationLabel = 'Support inbox';

    protected static UnitEnum|string|null $navigationGroup = 'Customers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('public_id')
                    ->label('Reference')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('subject')
                    ->searchable()
                    ->limit(60),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => Str::headline($state instanceof BackedEnum ? $state->value : (string) $state))
                    ->sortable(),
                TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => Str::headline($state instanceof BackedEnum ? $state->value : (string) $state))
                    ->sortable(),
                TextColumn::make('assignee.full_name')
                    ->label('Assigned to')
                    ->placeholder('Unassigned'),
                TextColumn::make('messages_count')
                    ->label('Messages')
                    ->sortable(),
                TextColumn::make('last_message_at')
                    ->label('Last activity')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'open' => 'Open',
                    'waiting_on_customer' => 'Waiting on customer',
                    'resolved' => 'Resolved',
                    'closed' => 'Closed',
                ]),
                SelectFilter::make('priority')->options([
                    'normal' => 'Normal',
                    'high' => 'High',
                    'urgent' => 'Urgent',
                ]),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open conversation')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (SupportTicket $record): string => route('staff.support.show', $record)),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'assignee'])
            ->withCount('messages')
            ->latest('last_message_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSupportTickets::route('/'),
        ];
    }
}
