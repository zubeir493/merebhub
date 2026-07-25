<?php

use App\Enums\AppSubmissionStatus;
use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('website_url')->nullable();
            $table->boolean('is_public')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('icon_path')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wc_product_id')->nullable()->unique();
            $table->foreignId('author_id')->constrained();
            $table->string('category')->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->string('icon_path')->nullable();
            $table->enum('status', array_column(ProductStatus::cases(), 'value'))->default(ProductStatus::Draft->value)->index();
            $table->timestamps();
        });

        Schema::create('platform_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['platform_id', 'product_id']);
        });

        Schema::create('app_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('submitter_name');
            $table->string('submitter_email')->index();
            $table->string('app_name');
            $table->text('description');
            $table->decimal('suggested_price', 10, 2)->nullable();
            $table->string('platform');
            $table->string('file_path');
            $table->enum('status', array_column(AppSubmissionStatus::cases(), 'value'))->default(AppSubmissionStatus::Pending->value)->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('linked_author_id')->nullable()->constrained('authors')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('version_number');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->text('changelog')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'version_number']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wc_order_id')->unique();
            $table->string('buyer_email')->index();
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('ETB');
            $table->enum('status', array_column(OrderStatus::cases(), 'value'))->default(OrderStatus::Pending->value)->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->string('buyer_email')->index();
            $table->string('keygen_license_id')->unique();
            $table->string('license_key')->unique();
            $table->enum('status', array_column(LicenseStatus::cases(), 'value'))->default(LicenseStatus::Active->value)->index();
            $table->unsignedInteger('activation_limit')->default(1);
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->string('event_type')->index();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['provider', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('licenses');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('app_versions');
        Schema::dropIfExists('app_submissions');
        Schema::dropIfExists('platform_product');
        Schema::dropIfExists('products');
        Schema::dropIfExists('platforms');
        Schema::dropIfExists('authors');
    }
};
