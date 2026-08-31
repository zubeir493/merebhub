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
        Schema::create('merchant_private_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')->unique()->constrained('merchants')->cascadeOnDelete();
            $table->text('registration_number')->nullable();
            $table->text('tax_identifier')->nullable();
            $table->text('payout_account')->nullable();
            $table->string('verification_status')->default('pending')->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_private_profiles');
    }
};
