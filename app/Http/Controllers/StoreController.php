<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrowseStoreRequest;
use App\Models\Platform;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function index(BrowseStoreRequest $request): View
    {
        return $this->renderStore($request, 'all');
    }

    public function newArrivals(BrowseStoreRequest $request): View
    {
        return $this->renderStore($request, 'newarrivals');
    }

    public function bestsellers(BrowseStoreRequest $request): View
    {
        return $this->renderStore($request, 'bestsellers');
    }

    public function deals(BrowseStoreRequest $request): View
    {
        return $this->renderStore($request, 'deals');
    }

    private function renderStore(BrowseStoreRequest $request, string $collection): View
    {
        $filters = $request->validated();
        $search = Str::of($filters['q'] ?? '')->squish()->toString();
        $category = $filters['category'] ?? '';
        $platform = $filters['platform'] ?? '';
        $collectionDetails = match ($collection) {
            'newarrivals' => ['New arrivals', 'The latest software published on MerebHub.', 'newest', 'store.newarrivals'],
            'bestsellers' => ['Best sellers', 'Popular software Ethiopian teams are choosing now.', 'popular', 'store.bestsellers'],
            'deals' => ['Deals', 'Limited-time savings on standout software.', 'popular', 'store.deals'],
            default => ['All software', 'Browse trusted apps, tools, and games from Ethiopian makers.', 'popular', 'store.index'],
        };
        $sort = $filters['sort'] ?? $collectionDetails[2];
        $products = Product::query()
            ->published()
            ->with('author')
            ->when($collection === 'deals', fn ($query) => $query
                ->whereNotNull('compare_at_price')
                ->whereColumn('compare_at_price', '>', 'price'))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('tagline', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")
                ->orWhereHas('author', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($platform !== '', fn ($query) => $query->whereHas(
                'platforms',
                fn ($query) => $query->where('platforms.slug', $platform),
            ));

        match ($sort) {
            'newest' => $products->latest(),
            'rating' => $products->orderByDesc('rating')->orderByDesc('ratings_count'),
            'price_asc' => $products->orderBy('price'),
            'price_desc' => $products->orderByDesc('price'),
            default => $products->orderByDesc('weekly_sales')->latest(),
        };

        return view('storefront.store', [
            'products' => $products->paginate(16)->withQueryString(),
            'categories' => Product::published()->distinct()->orderBy('category')->pluck('category'),
            'platforms' => Platform::query()->orderBy('name')->get(),
            'search' => $search,
            'category' => $category,
            'platform' => $platform,
            'sort' => $sort,
            'collection' => $collection,
            'heading' => $collectionDetails[0],
            'description' => $collectionDetails[1],
            'routeName' => $collectionDetails[3],
            'title' => $collectionDetails[0],
        ]);
    }
}
