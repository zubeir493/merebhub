<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Lunar\Core\Generators\UrlGenerator;

return new class extends Migration
{
    public function up(): void
    {
        $generator = app(UrlGenerator::class);

        Product::query()
            ->whereDoesntHave('urls')
            ->orderBy('id')
            ->get()
            ->each(fn (Product $product): mixed => $generator->handle($product));
    }

    public function down(): void
    {
        // Generated URLs are retained because this data migration cannot safely
        // distinguish them from URLs created by an administrator later.
    }
};
