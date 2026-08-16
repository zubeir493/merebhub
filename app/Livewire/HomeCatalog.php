<?php

namespace App\Livewire;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;

class HomeCatalog extends Component
{
    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $category = '';

    #[Url(history: true)]
    public string $platform = '';

    public function updatedSearch(): void
    {
        $this->reset('page');
    }

    public function updatedCategory(): void
    {
        $this->reset('page');
    }

    public function updatedPlatform(): void
    {
        $this->reset('page');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'category', 'platform', 'page']);
    }

    public function render(): View
    {
        $catalog = Product::published()->with(['author.defaultUrl', 'defaultUrl', 'media', 'variants.prices.currency', 'variants.prices.priceable']);
        $base = (clone $catalog)
            ->when($this->search !== '', fn (Builder $query) => $query->where('attribute_data', 'like', '%'.trim($this->search).'%'))
            ->when($this->category !== '', fn (Builder $query) => $query->where('attribute_data', 'like', "%{$this->category}%"))
            ->when($this->platform !== '', fn (Builder $query) => $query->where('attribute_data', 'like', '%'.str_replace('-', ' ', $this->platform).'%'));
        $allProducts = $catalog->get();

        return view('livewire.home-catalog', [
            'products' => $base->latest()->take(12)->get(),
            'featured' => $allProducts
                ->filter(fn (Product $product): bool => filter_var($product->attr('is_featured'), FILTER_VALIDATE_BOOL))
                ->take(4),
            'deals' => $allProducts->filter(fn (Product $product): bool => $product->compare_at_price !== null)->take(5),
            'topProducts' => $allProducts->sortByDesc('rating')->take(9),
            'categories' => $allProducts->pluck('category')->filter()->unique()->sort()->values(),
            'platforms' => $allProducts->flatMap->platforms->unique('slug')->sortBy('name')->values(),
        ]);
    }
}
