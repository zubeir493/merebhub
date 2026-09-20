<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductReviewRequest;
use App\Models\Author;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
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

    public function product(string $slug): View
    {
        $product = $this->products()
            ->with('publishedReviews.user')
            ->whereHas('defaultUrl', fn (Builder $query): Builder => $query->where('slug', $slug))
            ->firstOrFail();
        $wishlistItem = auth()->user()?->wishlistItems()
            ->where('product_id', $product->getKey())
            ->first();
        $galleryMedia = $product->media
            ->where('collection_name', config('lunar.media.collection'))
            ->sortBy('order_column')
            ->values();
        $user = auth()->user();

        return view('storefront.product', [
            'product' => $product,
            'galleryMedia' => $galleryMedia,
            'wishlistItem' => $wishlistItem,
            'canReview' => $user instanceof User && $product->wasPurchasedBy($user),
            'relatedProducts' => $this->products()
                ->whereKeyNot($product)
                ->catalogAttributeContains('attribute_data', $product->category)
                ->take(4)
                ->get(),
        ]);
    }

    public function storeReview(StoreProductReviewRequest $request, string $slug): RedirectResponse
    {
        $product = $this->products()
            ->whereHas('defaultUrl', fn (Builder $query): Builder => $query->where('slug', $slug))
            ->firstOrFail();
        $user = $request->user();

        if (! $user instanceof User || ! $product->wasPurchasedBy($user)) {
            return redirect()
                ->to(route('products.show', $product).'#product-reviews')
                ->withErrors(['review' => 'You can only review a product after purchasing it with this account.']);
        }

        ProductReview::updateOrCreate(
            [
                'product_id' => $product->getKey(),
                'user_id' => $user->getAuthIdentifier(),
            ],
            [
                ...$request->validated(),
                'status' => 'published',
            ],
        );

        return redirect()
            ->to(route('products.show', $product).'#product-reviews')
            ->with('status', 'Your review has been published.');
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
            ->when($search !== '', fn (Builder $query) => $query->search($search))
            ->when($category !== '', fn (Builder $query) => $query->catalogAttributeContains('attribute_data', $category));
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
            'variants.values',
        ]);
    }
}
