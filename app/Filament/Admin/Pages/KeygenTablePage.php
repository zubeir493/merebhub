<?php

namespace App\Filament\Admin\Pages;

use App\Integrations\Keygen\KeygenClient;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class KeygenTablePage extends Page implements HasTable
{
    use InteractsWithTable;

    public ?string $keygenError = null;

    protected function keygen(): KeygenClient
    {
        return app(KeygenClient::class);
    }

    protected function refreshRecords(): void
    {
        $this->flushCachedTableRecords();
    }

    /**
     * @param  callable(): array<int, array<string, mixed>>  $resolver
     * @return array<int, array<string, mixed>>
     */
    protected function safeRecords(callable $resolver): array
    {
        try {
            $this->keygenError = null;

            return $resolver();
        } catch (Throwable $exception) {
            Log::warning('Keygen admin request failed.', [
                'page' => static::class,
                'exception' => $exception::class,
            ]);

            $this->keygenError = 'Keygen is unavailable. Check that the server is running and that its URL and credentials are correct in Integration settings.';

            return [];
        }
    }

    protected function notifyFailure(string $title, Throwable $exception): void
    {
        Log::warning('Keygen admin action failed.', [
            'page' => static::class,
            'exception' => $exception::class,
        ]);

        $this->keygenError = 'Keygen is unavailable. Check Integration settings and the Keygen server health.';

        Notification::make()
            ->title($title)
            ->body($this->keygenError)
            ->danger()
            ->send();
    }
}
