<?php

namespace App\Filament\Resources\AppSubmissions;

use App\Enums\AppSubmissionStatus;
use App\Filament\Resources\AppSubmissions\Pages\ListAppSubmissions;
use App\Filament\Resources\AppSubmissions\Pages\ViewAppSubmission;
use App\Filament\Resources\AppSubmissions\Schemas\AppSubmissionForm;
use App\Filament\Resources\AppSubmissions\Schemas\AppSubmissionInfolist;
use App\Filament\Resources\AppSubmissions\Tables\AppSubmissionsTable;
use App\Models\AppSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AppSubmissionResource extends Resource
{
    protected static ?string $model = AppSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return AppSubmissionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppSubmissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppSubmissionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppSubmissions::route('/'),
            'view' => ViewAppSubmission::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) AppSubmission::where('status', AppSubmissionStatus::Pending)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
