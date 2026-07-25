<?php

namespace App\Filament\Resources\Authors\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AuthorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state)))->maxLength(255),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Textarea::make('bio')->rows(6)->columnSpanFull(),
            FileUpload::make('avatar_path')->image()->avatar()->disk('public')->directory('author-avatars')->imageEditor(),
            TextInput::make('website_url')->url()->maxLength(255),
            Toggle::make('is_public')->default(true)->required(),
        ])->columns(2);
    }
}
