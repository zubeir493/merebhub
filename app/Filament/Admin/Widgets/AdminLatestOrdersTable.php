<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Extensions\OrderTableExtension;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Lunar\Filament\Widgets\Dashboard\Orders\LatestOrdersTable as BaseLatestOrdersTable;

class AdminLatestOrdersTable extends BaseLatestOrdersTable
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['billingAddress']))
            ->columns(array_slice(OrderTableExtension::getOrderedColumns(), 0, 6));
    }
}
