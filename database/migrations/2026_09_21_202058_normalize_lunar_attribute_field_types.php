<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Lunar\Core\Enums\FieldTypeEnum;
use Lunar\Core\FieldTypes\TranslatedText;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('lunar.database.table_prefix').'attributes';

        DB::table($table)
            ->where('type', TranslatedText::class)
            ->update(['type' => FieldTypeEnum::TranslatedText->value]);
    }

    public function down(): void
    {
        $table = config('lunar.database.table_prefix').'attributes';

        DB::table($table)
            ->where('type', FieldTypeEnum::TranslatedText->value)
            ->update(['type' => TranslatedText::class]);
    }
};
