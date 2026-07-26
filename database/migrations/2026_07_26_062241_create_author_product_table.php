<?php

use App\Enums\AuthorRole;
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
        Schema::create('author_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default(AuthorRole::Contributor->value);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_publicly_displayed')->default(true);
            $table->boolean('can_manage_product')->default(false);
            $table->unsignedSmallInteger('revenue_share_basis_points')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->unique(['author_id', 'product_id']);
            $table->index(['product_id', 'is_primary']);
            $table->index(['author_id', 'is_publicly_displayed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('author_product');
    }
};
