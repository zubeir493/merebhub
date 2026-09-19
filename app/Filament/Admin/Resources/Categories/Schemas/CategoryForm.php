<?php

namespace App\Filament\Admin\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category details')
                    ->description('Name and icon used to organize the marketplace.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),
                        Hidden::make('slug'),
                    ]),
                Section::make('Storefront icon')
                    ->description('Choose an outline Heroicon for this category.')
                    ->schema([
                        Select::make('icon')
                            ->label('Heroicon')
                            ->options(Category::iconOptions())
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->default('squares-2x2')
                            ->helperText('Storefront icons always use the outline Heroicon set.'),
                    ]),
            ]);
    }
}
