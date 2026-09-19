<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('icon', 80)->default('squares-2x2');
            $table->timestamps();
        });

        $categoryNames = collect(array_keys(Category::defaultCategories()));

        Product::query()
            ->get()
            ->map(fn (Product $product): string => trim($product->category))
            ->filter()
            ->each(function (string $name) use (&$categoryNames): void {
                $categoryNames = $categoryNames->push($name);
            });

        $categoryNames
            ->unique()
            ->each(function (string $name): void {
                Category::query()->create([
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'icon' => Category::defaultIconFor($name),
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
