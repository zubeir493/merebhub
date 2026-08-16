<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $migration = require base_path('vendor/lunarphp/core/database/migrations/2026_01_01_900002_create_permission_tables.php');
        $migration->up();
    }

    public function down(): void
    {
        $migration = require base_path('vendor/lunarphp/core/database/migrations/2026_01_01_900002_create_permission_tables.php');
        $migration->down();
    }
};
