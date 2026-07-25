<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('tagline')->nullable();
            $table->string('cover_path')->nullable();
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->decimal('rating', 2, 1)->default(0);
            $table->unsignedInteger('ratings_count')->default(0);
            $table->unsignedInteger('weekly_sales')->default(0)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('keygen_policy_id')->nullable();
        });

        Schema::table('app_submissions', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->text('fulfillment_error')->nullable();
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->unique('order_id');
        });

        Schema::table('webhook_events', function (Blueprint $table) {
            $table->string('event_id')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropUnique(['provider', 'event_id']);
            $table->dropColumn(['event_id', 'attempts', 'last_error']);
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->dropUnique(['order_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('fulfillment_error');
        });

        Schema::table('app_submissions', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'tagline',
                'cover_path',
                'compare_at_price',
                'rating',
                'ratings_count',
                'weekly_sales',
                'is_featured',
                'keygen_policy_id',
            ]);
        });
    }
};
