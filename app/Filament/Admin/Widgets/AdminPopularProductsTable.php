<?php

namespace App\Filament\Admin\Widgets;

use Filament\Tables\Table;
use Lunar\Filament\Widgets\Dashboard\Orders\PopularProductsTable as BasePopularProductsTable;

class AdminPopularProductsTable extends BasePopularProductsTable
{
    protected int|string|array $columnSpan = ['lg' => 1, 'xl' => 1];

    protected string $view = 'filament.admin.widgets.best-sellers';

    public function table(Table $table): Table
    {
        return parent::table($table)->description(null);
    }
}
