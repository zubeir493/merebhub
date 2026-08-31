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
        Schema::create('merchants', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('type')->index();
            $table->string('legal_name')->nullable();
            $table->string('display_name');
            $table->string('slug')->unique();
            $table->text('profile')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('website_url')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('approved_at')->nullable()->index();
            $table->json('support_metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
