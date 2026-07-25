<?php

namespace App\Filament\Resources\AppSubmissions\Schemas;

use App\Enums\AppSubmissionStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AppSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('submitter_name')
                    ->required(),
                TextInput::make('submitter_email')
                    ->email()
                    ->required(),
                TextInput::make('app_name')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('suggested_price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('platform')
                    ->required(),
                TextInput::make('file_path')
                    ->required(),
                Select::make('status')
                    ->options(AppSubmissionStatus::class)
                    ->default('pending')
                    ->required(),
                TextInput::make('reviewed_by')
                    ->numeric(),
                Select::make('linked_author_id')
                    ->relationship('linkedAuthor', 'name'),
            ]);
    }
}
