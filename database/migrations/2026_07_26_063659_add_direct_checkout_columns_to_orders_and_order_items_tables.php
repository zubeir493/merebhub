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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('wc_order_id')->nullable()->change();
            $table->uuid('public_id')->nullable()->unique();
            $table->string('transaction_reference')->nullable()->unique();
            $table->unsignedBigInteger('subtotal_minor')->default(0);
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedBigInteger('total_minor')->default(0);
            $table->timestamp('payment_failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name')->nullable();
            $table->string('plan_name')->nullable();
            $table->unsignedBigInteger('unit_amount_minor')->default(0);
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedBigInteger('total_minor')->default(0);
            $table->char('currency', 3)->default('ETB');
            $table->json('primary_author_snapshot')->nullable();
            $table->unsignedSmallInteger('commission_basis_points')->default(0);
            $table->unsignedBigInteger('platform_share_minor')->default(0);
            $table->unsignedBigInteger('author_share_minor')->default(0);
            $table->string('billing_model')->nullable();
            $table->string('fulfillment_type')->nullable();
            $table->json('license_configuration')->nullable();
            $table->unsignedInteger('support_duration_days')->nullable();
            $table->unsignedInteger('update_duration_days')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_plan_id');
            $table->dropColumn([
                'product_name',
                'plan_name',
                'unit_amount_minor',
                'discount_minor',
                'total_minor',
                'currency',
                'primary_author_snapshot',
                'commission_basis_points',
                'platform_share_minor',
                'author_share_minor',
                'billing_model',
                'fulfillment_type',
                'license_configuration',
                'support_duration_days',
                'update_duration_days',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('wc_order_id')->nullable(false)->change();
            $table->dropColumn([
                'public_id',
                'transaction_reference',
                'subtotal_minor',
                'discount_minor',
                'total_minor',
                'payment_failed_at',
                'cancelled_at',
            ]);
        });
    }
};
