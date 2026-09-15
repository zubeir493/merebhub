<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $lunarPrefix = (string) config('lunar.database.table_prefix', 'lunar_');

        Schema::table('fulfillment_units', function (Blueprint $table) use ($lunarPrefix): void {
            $table->foreignId('product_variant_id')->nullable()
                ->after('product_id')
                ->constrained($lunarPrefix.'product_variants')
                ->nullOnDelete();
            $table->index(['product_id', 'product_variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fulfillment_units', function (Blueprint $table): void {
            $table->dropForeign(['product_variant_id']);
            $table->dropIndex(['product_id', 'product_variant_id']);
            $table->dropColumn('product_variant_id');
        });
    }
};
