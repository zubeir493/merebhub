<?php

namespace App\Livewire;

use App\Models\Category;
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
        $catalog = Product::published()
            ->withPublishedReviewSummary()
            ->with(['author.defaultUrl', 'defaultUrl', 'media', 'variants.prices.currency', 'variants.prices.priceable']);
        $base = (clone $catalog)
            ->when($this->search !== '', fn (Builder $query) => $query->search($this->search))
            ->when($this->category !== '', fn (Builder $query) => $query->catalogAttributeContains('attribute_data', $this->category))
            ->when($this->platform !== '', fn (Builder $query) => $query->catalogAttributeContains('attribute_data', str_replace('-', ' ', $this->platform)));
        $featuredProducts = (clone $catalog)
            ->where('is_featured', true)
            ->latest()
            ->take(4)
            ->get();
        $allProducts = $catalog->get();

        return view('livewire.home-catalog', [
            'products' => $base->latest()->take(12)->get(),
            'featured' => $featuredProducts,
            'deals' => $allProducts->filter(fn (Product $product): bool => $product->compare_at_price !== null)->take(5),
            'topProducts' => $allProducts->sortByDesc(fn (Product $product): float => $product->displayRating())->take(9),
            'categories' => Category::query()->orderBy('name')->get(),
            'platforms' => $allProducts->flatMap->platforms->unique('slug')->sortBy('name')->values(),
        ]);
    }
}
