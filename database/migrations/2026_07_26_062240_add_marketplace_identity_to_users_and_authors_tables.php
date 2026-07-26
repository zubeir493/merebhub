<?php

use App\Enums\AuthorStatus;
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
        Schema::table('authors', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('tagline')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('location')->nullable();
            $table->string('support_url')->nullable();
            $table->json('social_links')->nullable();
            $table->date('member_since')->nullable();
            $table->string('status')->default(AuthorStatus::Active->value)->index();
            $table->boolean('is_verified')->default(false)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('show_public_sales')->default(true);
            $table->unsignedBigInteger('public_sales_count')->default(0);
            $table->decimal('average_rating', 2, 1)->default(0);
            $table->string('public_support_instructions')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'tagline',
                'cover_path',
                'location',
                'support_url',
                'social_links',
                'member_since',
                'status',
                'is_verified',
                'is_featured',
                'show_public_sales',
                'public_sales_count',
                'average_rating',
                'public_support_instructions',
                'moderated_at',
            ]);
        });
    }
};
