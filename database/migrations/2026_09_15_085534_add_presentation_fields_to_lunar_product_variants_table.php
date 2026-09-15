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

        Schema::table($lunarPrefix.'product_variants', function (Blueprint $table): void {
            $table->string('presentation_icon', 80)->nullable();
            $table->string('presentation_image')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $lunarPrefix = (string) config('lunar.database.table_prefix', 'lunar_');

        Schema::table($lunarPrefix.'product_variants', function (Blueprint $table): void {
            $table->dropColumn(['presentation_icon', 'presentation_image']);
        });
    }
};
