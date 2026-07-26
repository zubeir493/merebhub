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
        Schema::create('earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->char('currency', 3)->default('ETB');
            $table->unsignedBigInteger('gross_minor');
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedBigInteger('net_minor');
            $table->unsignedBigInteger('platform_share_minor');
            $table->unsignedBigInteger('author_share_minor');
            $table->unsignedBigInteger('refund_deduction_minor')->default(0);
            $table->unsignedBigInteger('final_author_earnings_minor');
            $table->string('status')->default('pending')->index();
            $table->timestamp('earned_at');
            $table->timestamps();

            $table->unique(['order_item_id', 'author_id']);
            $table->index(['author_id', 'status', 'earned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('earnings');
    }
};
