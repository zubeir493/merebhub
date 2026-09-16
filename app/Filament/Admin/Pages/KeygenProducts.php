<?php

namespace App\Filament\Admin\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class KeygenProducts extends KeygenTablePage
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationLabel = 'Keygen products';

    protected static string|\UnitEnum|null $navigationGroup = 'Licensing';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.admin.pages.keygen-products';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => collect($this->keygen()->products())
                ->mapWithKeys(fn (array $record): array => [(string) $record['id'] => $this->record($record)])
                ->all())
            ->columns([
                TextColumn::make('name')->label('Name')->searchable(),
                TextColumn::make('code')->label('Code')->copyable()->searchable(),
                TextColumn::make('distributionStrategy')->label('Distribution')->badge(),
                TextColumn::make('url')->label('URL')->placeholder('—')->limit(45),
                TextColumn::make('id')->label('Keygen ID')->copyable()->limit(18),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Create product')
                    ->icon(Heroicon::Plus)
                    ->modalHeading('Create Keygen product')
                    ->schema($this->productSchema())
                    ->action(function (array $data): void {
                        try {
                            $this->keygen()->createProduct(
                                (string) $data['name'],
                                (string) $data['code'],
                                filled($data['url'] ?? null) ? (string) $data['url'] : null,
                                (string) $data['distributionStrategy'],
                            );
                            $this->refreshRecords();
                            Notification::make()->title('Keygen product created')->success()->send();
                        } catch (Throwable $exception) {
                            $this->notifyFailure('Product creation failed', $exception);
                        }
                    }),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon(Heroicon::PencilSquare)
                    ->fillForm(fn (array $record): array => [
                        'name' => $record['name'], 'code' => $record['code'], 'url' => $record['url'],
                        'distributionStrategy' => $record['distributionStrategy'],
                    ])
                    ->schema($this->productSchema())
                    ->action(function (array $data, array $record): void {
                        try {
                            $this->keygen()->updateProduct((string) $record['id'], [
                                'name' => (string) $data['name'],
                                'code' => (string) $data['code'],
                                'url' => filled($data['url'] ?? null) ? (string) $data['url'] : null,
                                'distributionStrategy' => (string) $data['distributionStrategy'],
                            ]);
                            $this->refreshRecords();
                            Notification::make()->title('Keygen product updated')->success()->send();
                        } catch (Throwable $exception) {
                            $this->notifyFailure('Product update failed', $exception);
                        }
                    }),
                Action::make('delete')
                    ->label('Delete')
                    ->icon(Heroicon::Trash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Keygen product?')
                    ->modalDescription('This also deletes its Keygen policies and licenses.')
                    ->action(function (array $record): void {
                        try {
                            $this->keygen()->deleteProduct((string) $record['id']);
                            $this->refreshRecords();
                            Notification::make()->title('Keygen product deleted')->success()->send();
                        } catch (Throwable $exception) {
                            $this->notifyFailure('Product deletion failed', $exception);
                        }
                    }),
            ]);
    }

    /** @return array<string, mixed> */
    private function record(array $record): array
    {
        $attributes = (array) ($record['attributes'] ?? []);

        return [
            'id' => (string) $record['id'],
            'name' => (string) ($attributes['name'] ?? ''),
            'code' => (string) ($attributes['code'] ?? ''),
            'url' => $attributes['url'] ?? null,
            'distributionStrategy' => (string) ($attributes['distributionStrategy'] ?? ''),
        ];
    }

    /** @return array<int, Component> */
    private function productSchema(): array
    {
        return [
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('code')->required()->maxLength(255),
            TextInput::make('url')->url()->nullable(),
            Select::make('distributionStrategy')->label('Distribution strategy')->options([
                'LICENSED' => 'Licensed', 'OPEN' => 'Open',
            ])->default('LICENSED')->required(),
        ];
    }

    private function notifyFailure(string $title, Throwable $exception): void
    {
        report($exception);
        Notification::make()->title($title)->body($exception->getMessage())->danger()->send();
    }
}
