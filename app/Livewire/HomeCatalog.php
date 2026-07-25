<?php

namespace App\Livewire;

use App\Models\Platform;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class HomeCatalog extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $category = '';

    #[Url(history: true)]
    public string $platform = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedPlatform(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'category', 'platform']);
        $this->resetPage();
    }

    public function render(): View
    {
        $base = Product::published()->with(['author', 'platforms']);

        if ($this->search !== '') {
            $term = '%'.trim($this->search).'%';
            $base->where(fn ($query) => $query
                ->where('name', 'like', $term)
                ->orWhere('tagline', 'like', $term)
                ->orWhere('description', 'like', $term)
                ->orWhere('category', 'like', $term)
                ->orWhereHas('author', fn ($author) => $author->where('name', 'like', $term)));
        }

        $base
            ->when($this->category !== '', fn ($query) => $query->where('category', $this->category))
            ->when($this->platform !== '', fn ($query) => $query->whereHas(
                'platforms',
                fn ($platform) => $platform->where('platforms.slug', $this->platform),
            ));

        return view('livewire.home-catalog', [
            'products' => (clone $base)->latest()->paginate(12),
            'featured' => Product::published()->with('author')->where('is_featured', true)->latest()->take(4)->get(),
            'deals' => Product::published()->with('author')->whereNotNull('compare_at_price')->latest()->take(5)->get(),
            'topProducts' => Product::published()->with(['author', 'platforms'])->orderByDesc('weekly_sales')->take(9)->get(),
            'categories' => Product::published()->distinct()->orderBy('category')->pluck('category'),
            'platforms' => Platform::query()->orderBy('name')->get(),
        ]);
    }
}
