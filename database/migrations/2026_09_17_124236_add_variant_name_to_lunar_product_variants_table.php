<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $variantsTable = (string) config('lunar.database.table_prefix', 'lunar_').'product_variants';

        Schema::table($variantsTable, function (Blueprint $table): void {
            $table->string('variant_name')->nullable()->after('sku');
        });

        $mappingLabels = Schema::hasTable('license_mappings')
            ? DB::table('license_mappings')->whereNotNull('label')->pluck('label', 'product_variant_id')
            : collect();

        DB::table($variantsTable)
            ->select(['id', 'sku'])
            ->orderBy('id')
            ->get()
            ->each(function (object $variant) use ($mappingLabels, $variantsTable): void {
                $mappingLabel = trim((string) ($mappingLabels[$variant->id] ?? ''));
                $sku = trim((string) $variant->sku);
                $variantName = $mappingLabel !== ''
                    ? $mappingLabel
                    : (Str::contains(Str::upper($sku), 'STANDARD') ? 'Standard license' : ($sku ?: 'Standard license'));

                DB::table($variantsTable)
                    ->where('id', $variant->id)
                    ->update(['variant_name' => $variantName]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $variantsTable = (string) config('lunar.database.table_prefix', 'lunar_').'product_variants';

        Schema::table($variantsTable, function (Blueprint $table): void {
            $table->dropColumn('variant_name');
        });
    }
};
