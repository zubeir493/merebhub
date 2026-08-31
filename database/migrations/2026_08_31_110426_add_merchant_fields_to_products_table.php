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
        $productsTable = config('lunar.database.table_prefix').'products';

        Schema::table($productsTable, function (Blueprint $table): void {
            $table->foreignId('merchant_id')->nullable()->constrained('merchants')->nullOnDelete();
            $table->string('source_type')->default('local_developer')->index();
            $table->string('publication_state')->default('draft')->index();
            $table->unsignedBigInteger('current_revision_id')->nullable();
            $table->string('support_owner')->default('merebhub');
            $table->boolean('official_partner')->default(false)->index();
            $table->json('fulfillment_summary')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('archived_at')->nullable()->index();

            $table->index(['merchant_id', 'publication_state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $productsTable = config('lunar.database.table_prefix').'products';

        Schema::table($productsTable, function (Blueprint $table): void {
            $table->dropForeign(['merchant_id']);
            $table->dropIndex(['merchant_id', 'publication_state']);
            $table->dropColumn([
                'merchant_id',
                'source_type',
                'publication_state',
                'current_revision_id',
                'support_owner',
                'official_partner',
                'fulfillment_summary',
                'published_at',
                'archived_at',
            ]);
        });
    }
};
