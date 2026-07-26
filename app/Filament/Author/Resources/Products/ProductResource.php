<?php

namespace App\Filament\Author\Resources\Products;

use App\Enums\ProductStatus;
use App\Filament\Author\Resources\Products\Pages\ManageProducts;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    public static function getEloquentQuery(): Builder
    {
        $author = auth()->user()->authorProfile;

        return parent::getEloquentQuery()
            ->where(fn (Builder $query) => $query
                ->whereBelongsTo($author, 'author')
                ->orWhereHas('authors', fn (Builder $query) => $query
                    ->whereKey($author->getKey())
                    ->where('author_product.can_manage_product', true)));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('author_id')->default(fn (): int => auth()->user()->authorProfile->id),
                Hidden::make('status')->default(ProductStatus::Draft),
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state)))
                    ->maxLength(255),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                TextInput::make('category')->required()->maxLength(255),
                TextInput::make('tagline')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->required()->rows(10)->columnSpanFull(),
                FileUpload::make('cover_path')
                    ->image()
                    ->disk('public')
                    ->directory('product-covers')
                    ->imageEditor(),
                TextInput::make('app_url')->label('Demo or app URL')->url()->maxLength(255),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                TextColumn::make('category')->badge(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProducts::route('/'),
        ];
    }
}
