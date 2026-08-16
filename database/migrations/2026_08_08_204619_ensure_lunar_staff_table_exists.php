<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lunar_staff')) {
            return;
        }

        Schema::create('lunar_staff', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->boolean('admin')->default(false)->index();
            $table->string('first_name')->index();
            $table->string('last_name')->index();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lunar_staff');
    }
};
