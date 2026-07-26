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
        Schema::table('app_versions', function (Blueprint $table) {
            $table->char('sha256_checksum', 64)->nullable()->index();
            $table->text('release_notes')->nullable();
            $table->string('release_status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('minimum_supported_version')->nullable();
            $table->string('scan_status')->default('pending')->index();
            $table->timestamp('scanned_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_versions', function (Blueprint $table) {
            $table->dropColumn([
                'sha256_checksum',
                'release_notes',
                'release_status',
                'published_at',
                'minimum_supported_version',
                'scan_status',
                'scanned_at',
            ]);
        });
    }
};
