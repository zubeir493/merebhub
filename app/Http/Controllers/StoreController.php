<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrowseStoreRequest;
use App\Models\Author;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
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

    public function search(BrowseStoreRequest $request): RedirectResponse
    {
        return redirect()->route('store.index', $request->safe()->only([
            'q',
            'category',
            'platform',
            'sort',
        ]));
    }

    private function renderStore(BrowseStoreRequest $request, string $collection): View
    {
        $filters = $request->validated();
        $search = Str::of($filters['q'] ?? '')->squish()->toString();
        $category = $filters['category'] ?? '';
        $platform = $filters['platform'] ?? '';
        [$heading, $description, $defaultSort, $routeName] = match ($collection) {
            'newarrivals' => ['Fresh tools, thoughtfully made.', 'The newest releases from Ethiopian software makers.', 'newest', 'store.newarrivals'],
            'bestsellers' => ['What customers keep choosing.', 'The tools earning a place in more workflows.', 'popular', 'store.bestsellers'],
            'deals' => ['Good tools, better prices.', 'Current offers from across the marketplace.', 'popular', 'store.deals'],
            default => ['Software worth using.', 'Independent tools from Ethiopian makers, all in one place.', 'popular', 'store.index'],
        };
        $sort = $filters['sort'] ?? $defaultSort;
        $base = Product::published()
            ->withPublishedReviewSummary()
            ->with(['author.defaultUrl', 'defaultUrl', 'media', 'variants.prices.currency', 'variants.prices.priceable']);
        $products = (clone $base)
            ->when($collection === 'deals', fn (Builder $query) => $query->whereHas('prices', fn (Builder $query) => $query->whereNotNull('list_price')))
            ->when($search !== '', fn (Builder $query) => $query->search($search))
            ->when($category !== '', fn (Builder $query) => $query->catalogAttributeContains('attribute_data', $category))
            ->when($platform !== '', fn (Builder $query) => $query->catalogAttributeContains('attribute_data', str_replace('-', ' ', $platform)))
            ->withMin('prices', 'price');

        match ($sort) {
            'price_asc' => $products->orderBy('prices_min_price'),
            'price_desc' => $products->orderByDesc('prices_min_price'),
            'rating' => $products->orderByDesc('published_reviews_avg_rating')->latest(),
            default => $products->latest(),
        };

        $catalogProducts = $base->get();
        $platforms = $catalogProducts->flatMap->platforms->unique('slug')->sortBy('name')->values();
        $authors = $search === ''
            ? collect()
            : Author::query()
                ->with('defaultUrl')
                ->withCount(['products' => fn (Builder $query) => $query->where('status', 'published')])
                ->where('name', 'like', "%{$search}%")
                ->latest()
                ->limit(6)
                ->get();

        return view('storefront.store', [
            'products' => $products->paginate(16)->withQueryString(),
            'categories' => Category::query()->orderBy('name')->get(),
            'platforms' => $platforms,
            'authors' => $authors,
            'search' => $search,
            'category' => $category,
            'platform' => $platform,
            'sort' => $sort,
            'defaultSort' => $defaultSort,
            'heading' => $heading,
            'description' => $description,
            'routeName' => $routeName,
            'title' => $search === '' ? $heading : "Search results for {$search}",
        ]);
    }
}
