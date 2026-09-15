<?php

namespace App\Filament\Admin\Resources\LicenseMappings\Pages;

use App\Filament\Admin\Resources\LicenseMappings\LicenseMappingResource;
use App\Integrations\Keygen\KeygenClient;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListLicenseMappings extends ListRecords
{
    protected static string $resource = LicenseMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('testKeygenConnection')
                ->label('Test Keygen connection')
                ->icon('heroicon-o-signal')
                ->action(function (KeygenClient $client): void {
                    try {
                        $account = $client->account();
                    } catch (Throwable $exception) {
                        report($exception);
                        Notification::make()
                            ->title('Keygen connection failed')
                            ->body('Check the Keygen URL, account ID, token, and TLS settings.')
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Keygen connection is healthy')
                        ->body((string) (data_get($account, 'data.attributes.name') ?? 'Account verified'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
