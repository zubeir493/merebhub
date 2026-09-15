<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $lunarPrefix = (string) config('lunar.database.table_prefix', 'lunar_');

        Schema::create('license_mappings', function (Blueprint $table) use ($lunarPrefix): void {
            $table->id();
            $table->foreignId('product_id')->constrained($lunarPrefix.'products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained($lunarPrefix.'product_variants')->nullOnDelete();
            $table->string('keygen_product_id')->nullable();
            $table->string('keygen_policy_id');
            $table->string('label')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(['product_id', 'product_variant_id']);
            $table->index(['keygen_product_id', 'keygen_policy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_mappings');
    }
};
