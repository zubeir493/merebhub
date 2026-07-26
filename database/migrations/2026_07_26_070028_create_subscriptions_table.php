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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('license_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('previous_subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->index();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('last_reminded_at')->nullable();
            $table->unsignedSmallInteger('last_reminder_days')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
