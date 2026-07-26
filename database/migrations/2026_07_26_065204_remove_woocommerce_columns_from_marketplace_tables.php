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
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropUnique(['wc_order_item_id']);
            $table->dropColumn('wc_order_item_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['wc_order_id']);
            $table->dropColumn('wc_order_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['wc_product_id']);
            $table->dropIndex(['last_synced_at']);
            $table->dropColumn(['wc_product_id', 'wc_metadata', 'last_synced_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('wc_product_id')->nullable()->unique();
            $table->json('wc_metadata')->nullable();
            $table->timestamp('last_synced_at')->nullable()->index();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('wc_order_id')->nullable()->unique();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('wc_order_item_id')->nullable()->unique();
        });
    }
};
