<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Lunar\Core\Models\Url;

class StorefrontController extends Controller
{
    public function home(): View
    {
        return view('storefront.home');
    }

    public function search(Request $request): View
    {
        $search = Str::of($request->string('q'))->squish()->limit(100)->toString();
        $products = $this->products()
            ->when($search !== '', fn (Builder $query) => $query->where('attribute_data', 'like', "%{$search}%"), fn (Builder $query) => $query->whereKey([]))
            ->latest()
            ->paginate(12)
            ->withQueryString();
        $authors = Author::query()
            ->with(['defaultUrl'])
            ->withCount(['products' => fn (Builder $query) => $query->where('status', 'published')])
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"), fn (Builder $query) => $query->whereKey([]))
            ->latest()
            ->limit(6)
            ->get();

        return view('storefront.search', [
            'products' => $products,
            'authors' => $authors,
            'search' => $search,
            'title' => $search === '' ? 'Search' : "Search results for {$search}",
        ]);
    }

    public function product(string $slug): View
    {
        $productId = Url::query()
            ->where('slug', $slug)
            ->where('element_type', (new Product)->getMorphClass())
            ->value('element_id');
        $product = $this->products()->findOrFail($productId);

        return view('storefront.product', [
            'product' => $product,
            'relatedProducts' => $this->products()
                ->whereKeyNot($product)
                ->where('attribute_data', 'like', '%'.$product->category.'%')
                ->take(4)
                ->get(),
        ]);
    }

    public function vendors(Request $request): View
    {
        $search = Str::of($request->string('q'))->squish()->limit(100)->toString();
        $sort = $request->string('sort', 'newest')->toString();
        $vendors = Author::query()
            ->with('defaultUrl')
            ->withCount(['products' => fn (Builder $query) => $query->where('status', 'published')])
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
            ->when($sort === 'products', fn (Builder $query) => $query->orderByDesc('products_count'), fn (Builder $query) => $query->latest())
            ->paginate(12)
            ->withQueryString();

        return view('storefront.vendors', compact('vendors', 'search', 'sort'));
    }

    public function vendor(Request $request, string $slug): View
    {
        $authorId = Url::query()
            ->where('slug', $slug)
            ->where('element_type', (new Author)->getMorphClass())
            ->value('element_id');
        $author = Author::query()->with('defaultUrl')->findOrFail($authorId);
        $search = Str::of($request->string('q'))->squish()->limit(100)->toString();
        $category = $request->string('category')->trim()->toString();
        $sort = $request->string('sort', 'newest')->toString();
        $query = $this->products()
            ->whereBelongsTo($author, 'author')
            ->when($search !== '', fn (Builder $query) => $query->where('attribute_data', 'like', "%{$search}%"))
            ->when($category !== '', fn (Builder $query) => $query->where('attribute_data', 'like', "%{$category}%"));
        $products = (clone $query)->latest()->paginate(12)->withQueryString();
        $categories = (clone $query)->get()->pluck('category')->filter()->unique()->sort()->values();

        return view('storefront.author', compact('author', 'products', 'categories', 'search', 'category', 'sort'));
    }

    private function products(): Builder
    {
        return Product::published()->with([
            'author.defaultUrl',
            'defaultUrl',
            'media',
            'variants.prices.currency',
            'variants.prices.priceable',
        ]);
    }
}
