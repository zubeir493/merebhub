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

        Schema::create('fulfillment_units', function (Blueprint $table) use ($lunarPrefix): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('order_id')->constrained($lunarPrefix.'orders')->cascadeOnDelete();
            $table->foreignId('order_line_id')->constrained($lunarPrefix.'order_lines')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained($lunarPrefix.'products')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('type')->default('license')->index();
            $table->string('provider')->default('fake-keygen')->index();
            $table->string('status')->default('pending')->index();
            $table->string('idempotency_key')->unique();
            $table->string('external_id')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        Schema::create('fulfillment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fulfillment_unit_id')->constrained('fulfillment_units')->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->string('status')->index();
            $table->string('error_class')->nullable();
            $table->text('error_message')->nullable();
            $table->string('provider_request_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['fulfillment_unit_id', 'attempt_number']);
        });

        Schema::create('entitlements', function (Blueprint $table) use ($lunarPrefix): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained($lunarPrefix.'orders')->cascadeOnDelete();
            $table->foreignId('order_line_id')->constrained($lunarPrefix.'order_lines')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained($lunarPrefix.'products')->nullOnDelete();
            $table->foreignId('fulfillment_unit_id')->unique()->constrained('fulfillment_units')->cascadeOnDelete();
            $table->string('type')->default('license')->index();
            $table->string('status')->default('active')->index();
            $table->string('provider')->index();
            $table->string('external_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('credentials', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('entitlement_id')->unique()->constrained('entitlements')->cascadeOnDelete();
            $table->string('type')->default('license_key');
            $table->text('secret');
            $table->timestamp('revealed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('provider_mirrors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fulfillment_unit_id')->constrained('fulfillment_units')->cascadeOnDelete();
            $table->string('provider');
            $table->string('operation');
            $table->string('idempotency_key');
            $table->string('external_id')->nullable();
            $table->string('status')->index();
            $table->json('response')->nullable();
            $table->timestamps();

            $table->unique(['fulfillment_unit_id', 'operation']);
            $table->unique(['provider', 'idempotency_key']);
        });

        Schema::create('downloadable_assets', function (Blueprint $table) use ($lunarPrefix): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('product_id')->nullable()->constrained($lunarPrefix.'products')->nullOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('filename');
            $table->string('checksum', 128)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('scan_status')->default('pending')->index();
            $table->timestamp('scanned_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['disk', 'path']);
            $table->index(['product_id', 'scan_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downloadable_assets');
        Schema::dropIfExists('provider_mirrors');
        Schema::dropIfExists('credentials');
        Schema::dropIfExists('entitlements');
        Schema::dropIfExists('fulfillment_attempts');
        Schema::dropIfExists('fulfillment_units');
    }
};
