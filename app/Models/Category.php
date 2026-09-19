<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'icon',
    ];

    protected static function booted(): void
    {
        static::saving(function (Category $category): void {
            if (blank($category->slug)) {
                $category->slug = Str::slug((string) $category->name);
            }

            $category->icon = static::normaliseIcon($category->icon);
        });
    }

    /** @return array<string, string> */
    public static function iconOptions(): array
    {
        return [
            'academic-cap' => 'Academic cap',
            'adjustments-horizontal' => 'Adjustments horizontal',
            'briefcase' => 'Briefcase',
            'chart-bar' => 'Chart bar',
            'chat-bubble-left-right' => 'Chat bubble',
            'cloud' => 'Cloud',
            'code-bracket' => 'Code bracket',
            'command-line' => 'Command line',
            'cpu-chip' => 'CPU chip',
            'cube' => 'Cube',
            'cursor-arrow-rays' => 'Cursor rays',
            'document-chart-bar' => 'Document chart',
            'megaphone' => 'Megaphone',
            'pencil-square' => 'Pencil square',
            'puzzle-piece' => 'Puzzle piece',
            'rocket-launch' => 'Rocket launch',
            'shield-check' => 'Shield check',
            'sparkles' => 'Sparkles',
            'squares-2x2' => 'Squares',
            'swatch' => 'Swatch',
            'wrench-screwdriver' => 'Wrench screwdriver',
        ];
    }

    public static function defaultIconFor(string $name): string
    {
        $normalisedName = Str::lower(trim($name));

        return collect(static::defaultCategories())
            ->first(fn (string $icon, string $category): bool => Str::lower($category) === $normalisedName)
            ?? 'squares-2x2';
    }

    /** @return array<string, string> */
    public static function defaultCategories(): array
    {
        return [
            'Business' => 'briefcase',
            'Developer tools' => 'code-bracket',
            'Design' => 'pencil-square',
            'Security' => 'shield-check',
            'Games' => 'puzzle-piece',
            'Marketing' => 'megaphone',
            'Productivity' => 'adjustments-horizontal',
            'Data & analytics' => 'chart-bar',
        ];
    }

    public function iconComponent(): string
    {
        return 'heroicon-o-'.static::normaliseIcon($this->icon);
    }

    private static function normaliseIcon(?string $icon): string
    {
        $icon = Str::lower(trim((string) $icon));

        return array_key_exists($icon, static::iconOptions()) ? $icon : 'squares-2x2';
    }
}
