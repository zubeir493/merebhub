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
        Schema::table('licenses', function (Blueprint $table) {
            $table->uuid('marketplace_license_id')->nullable()->unique();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('keygen')->index();
            $table->string('provider_product_id')->nullable();
            $table->string('provider_policy_id')->nullable();
            $table->string('provider_license_id')->nullable()->unique();
            $table->unsignedInteger('activation_count')->default(0);
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('product_plan_id');
            $table->dropColumn([
                'marketplace_license_id',
                'provider',
                'provider_product_id',
                'provider_policy_id',
                'provider_license_id',
                'activation_count',
                'issued_at',
                'suspended_at',
            ]);
        });
    }
};
