<?php

namespace App\Filament\Author\Resources\Authors;

use App\Filament\Author\Resources\Authors\Pages\ManageAuthors;
use App\Models\Author;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Public profile';

    protected static ?string $modelLabel = 'public profile';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereBelongsTo(auth()->user(), 'user');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Display name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('tagline')->maxLength(255),
                Textarea::make('bio')
                    ->rows(8)
                    ->columnSpanFull(),
                FileUpload::make('avatar_path')
                    ->image()
                    ->avatar()
                    ->disk('public')
                    ->directory('author-avatars')
                    ->imageEditor(),
                FileUpload::make('cover_path')
                    ->image()
                    ->disk('public')
                    ->directory('author-covers')
                    ->imageEditor(),
                TextInput::make('location')->maxLength(255),
                TextInput::make('website_url')
                    ->url()
                    ->maxLength(255),
                TextInput::make('support_url')
                    ->url()
                    ->maxLength(255),
                Textarea::make('public_support_instructions')
                    ->rows(4)
                    ->columnSpanFull(),
                KeyValue::make('social_links')
                    ->keyLabel('Network')
                    ->valueLabel('Profile URL')
                    ->columnSpanFull(),
                Toggle::make('is_public')
                    ->label('Show my approved profile publicly')
                    ->required(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->weight('bold'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('public_sales_count')
                    ->label('Sales')
                    ->numeric(),
                TextColumn::make('average_rating')
                    ->label('Rating')
                    ->numeric(1),
                TextColumn::make('updated_at')
                    ->label('Last updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAuthors::route('/'),
        ];
    }
}
