<?php

namespace App\Filament\Resources\AppSubmissions\Tables;

use App\Enums\AppSubmissionStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AppSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('app_name')->label('App')->searchable()->sortable()->weight('bold'),
                TextColumn::make('submitter_name')->label('Submitter')->description(fn ($record) => $record->submitter_email)->searchable(['submitter_name', 'submitter_email']),
                TextColumn::make('platform')->badge()->searchable(),
                TextColumn::make('suggested_price')->money('ETB')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(AppSubmissionStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('No submissions waiting')
            ->emptyStateDescription('New public submissions will appear here.');
    }
}
