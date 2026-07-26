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
        Schema::create('product_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_minor');
            $table->char('currency', 3)->default('ETB');
            $table->string('billing_model')->default(BillingModel::OneTime->value)->index();
            $table->string('billing_interval')->nullable();
            $table->string('license_type')->default('perpetual');
            $table->unsignedInteger('license_duration_days')->nullable();
            $table->unsignedInteger('activation_limit')->default(1);
            $table->json('entitlements')->nullable();
            $table->unsignedInteger('support_duration_days')->nullable();
            $table->unsignedInteger('update_duration_days')->nullable();
            $table->unsignedInteger('download_limit')->nullable();
            $table->string('keygen_policy_id')->nullable();
            $table->string('fulfillment_type')->default(FulfillmentType::LicenseKey->value);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'slug']);
            $table->index(['product_id', 'is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_plans');
    }
};
