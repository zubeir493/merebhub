<?php

namespace App\Filament\Resources\Authors\Schemas;

use App\Enums\AuthorStatus;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
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
            Select::make('user_id')->relationship('user', 'email')->searchable()->preload()->unique(ignoreRecord: true),
            TextInput::make('tagline')->maxLength(255),
            Textarea::make('bio')->rows(6)->columnSpanFull(),
            FileUpload::make('avatar_path')->image()->avatar()->disk('public')->directory('author-avatars')->imageEditor(),
            FileUpload::make('cover_path')->image()->disk('public')->directory('author-covers')->imageEditor(),
            TextInput::make('location')->maxLength(255),
            TextInput::make('website_url')->url()->maxLength(255),
            TextInput::make('support_url')->url()->maxLength(255),
            KeyValue::make('social_links')->columnSpanFull(),
            Select::make('status')->options(AuthorStatus::class)->required()->default(AuthorStatus::Active),
            Toggle::make('is_public')->default(true)->required(),
            Toggle::make('is_verified'),
            Toggle::make('is_featured'),
            Toggle::make('show_public_sales')->default(true),
            TextInput::make('public_sales_count')->numeric()->minValue(0)->disabled()->dehydrated(false),
            TextInput::make('average_rating')->numeric()->minValue(0)->maxValue(5)->disabled()->dehydrated(false),
            Textarea::make('public_support_instructions')->rows(4)->columnSpanFull(),
        ])->columns(2);
    }
}
