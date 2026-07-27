<?php

use App\Enums\BillingModel;
use App\Enums\FulfillmentType;
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
        Schema::table('products', function (Blueprint $table) {
            $table->string('fulfillment_type')->default(FulfillmentType::LicenseKey->value)->index();
            $table->string('billing_model')->default(BillingModel::OneTime->value)->index();
            $table->string('billing_interval')->nullable()->index();
            $table->unsignedSmallInteger('trial_days')->nullable();
            $table->string('app_url')->nullable();
            $table->json('wc_metadata')->nullable();
            $table->timestamp('last_synced_at')->nullable()->index();
        });

        Schema::table('app_submissions', function (Blueprint $table) {
            $table->string('category')->nullable();
            $table->string('demo_url')->nullable();
            $table->string('fulfillment_type')->default(FulfillmentType::LicenseKey->value)->index();
            $table->string('payment_model')->default(BillingModel::OneTime->value)->index();
            $table->string('billing_interval')->nullable();
            $table->unsignedSmallInteger('trial_days')->nullable();
            $table->string('file_path')->nullable()->change();
        });

        Schema::create('submission_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_submission_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_url')->nullable();
            $table->foreignId('product_id')->nullable()->change();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->unsignedBigInteger('wc_order_item_id')->nullable()->unique();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_amount', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->unique(['order_id', 'product_id']);
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropUnique(['order_id']);
            $table->foreignId('order_item_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            $table->unique('order_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropUnique(['order_item_id']);
            $table->dropConstrainedForeignId('order_item_id');
            $table->unique('order_id');
        });

        Schema::dropIfExists('order_items');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_url');
            $table->foreignId('product_id')->nullable(false)->change();
        });

        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('submission_attachments');

        Schema::table('app_submissions', function (Blueprint $table) {
            $table->string('file_path')->nullable(false)->change();
            $table->dropColumn([
                'category',
                'demo_url',
                'fulfillment_type',
                'payment_model',
                'billing_interval',
                'trial_days',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'fulfillment_type',
                'billing_model',
                'billing_interval',
                'trial_days',
                'app_url',
                'wc_metadata',
                'last_synced_at',
            ]);
        });
    }
};
