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
        Schema::table('outbox_messages', function (Blueprint $table): void {
            $table->string('dedupe_key')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outbox_messages', function (Blueprint $table): void {
            $table->dropUnique(['dedupe_key']);
            $table->dropColumn('dedupe_key');
        });
    }
};
