<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrowseStoreRequest;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
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
        $details = match ($collection) {
            'newarrivals' => ['New arrivals', 'The latest software published on MerebHub.', 'newest', 'store.newarrivals'],
            'bestsellers' => ['Best sellers', 'Popular software Ethiopian teams are choosing now.', 'popular', 'store.bestsellers'],
            'deals' => ['Deals', 'Limited-time savings on standout software.', 'popular', 'store.deals'],
            default => ['All software', 'Browse trusted apps, tools, and games from Ethiopian makers.', 'popular', 'store.index'],
        };
        $sort = $filters['sort'] ?? $details[2];
        $base = Product::published()->with(['author.defaultUrl', 'defaultUrl', 'media', 'variants.prices.currency', 'variants.prices.priceable']);
        $products = (clone $base)
            ->when($collection === 'deals', fn (Builder $query) => $query->whereHas('prices', fn (Builder $query) => $query->whereNotNull('list_price')))
            ->when($search !== '', fn (Builder $query) => $query->where('attribute_data', 'like', "%{$search}%"))
            ->when($category !== '', fn (Builder $query) => $query->where('attribute_data', 'like', "%{$category}%"))
            ->when($platform !== '', fn (Builder $query) => $query->where('attribute_data', 'like', '%'.str_replace('-', ' ', $platform).'%'))
            ->withMin('prices', 'price');

        match ($sort) {
            'price_asc' => $products->orderBy('prices_min_price'),
            'price_desc' => $products->orderByDesc('prices_min_price'),
            default => $products->latest(),
        };

        $catalogProducts = $base->get();
        $categories = $catalogProducts->pluck('category')->filter()->unique()->sort()->values();
        $platforms = $catalogProducts->flatMap->platforms->unique('slug')->sortBy('name')->values();

        return view('storefront.store', [
            'products' => $products->paginate(16)->withQueryString(),
            'categories' => $categories,
            'platforms' => $platforms,
            'search' => $search,
            'category' => $category,
            'platform' => $platform,
            'sort' => $sort,
            'collection' => $collection,
            'heading' => $details[0],
            'description' => $details[1],
            'routeName' => $details[3],
            'title' => $details[0],
        ]);
    }
}
