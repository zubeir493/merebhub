<?php

namespace App\Filament\Admin\Pages;

use App\Integrations\Keygen\KeygenClient;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

abstract class KeygenTablePage extends Page implements HasTable
{
    use InteractsWithTable;

    protected function keygen(): KeygenClient
    {
        return app(KeygenClient::class);
    }

    protected function refreshRecords(): void
    {
        $this->flushCachedTableRecords();
    }
}
