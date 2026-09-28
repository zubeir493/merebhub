<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = (string) config('lunar.database.table_prefix', 'lunar_');
        $currenciesTable = $prefix.'currencies';
        $pricesTable = $prefix.'prices';
        $cartsTable = $prefix.'carts';

        $ethiopianBirrId = DB::table($currenciesTable)
            ->where('code', 'ETB')
            ->value('id');

        if ($ethiopianBirrId === null) {
            return;
        }

        $pesoId = DB::table($currenciesTable)
            ->where('code', 'PHP')
            ->value('id');

        DB::transaction(function () use (
            $currenciesTable,
            $pricesTable,
            $cartsTable,
            $ethiopianBirrId,
            $pesoId,
        ): void {
            DB::table($currenciesTable)->update(['default' => false]);
            DB::table($currenciesTable)
                ->where('id', $ethiopianBirrId)
                ->update([
                    'name' => 'Ethiopian Birr',
                    'exchange_rate' => 1,
                    'enabled' => true,
                    'default' => true,
                ]);

            if ($pesoId === null) {
                return;
            }

            DB::table($pricesTable)
                ->where('currency_id', $pesoId)
                ->update(['currency_id' => $ethiopianBirrId]);
            DB::table($cartsTable)
                ->where('currency_id', $pesoId)
                ->update(['currency_id' => $ethiopianBirrId]);
            DB::table($currenciesTable)
                ->where('id', $pesoId)
                ->update([
                    'enabled' => false,
                    'default' => false,
                ]);
        });
    }

    public function down(): void
    {
        // This corrective migration should not restore the invalid peso default.
    }
};
