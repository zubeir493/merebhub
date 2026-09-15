<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Lunar\Core\Models\Url;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        $items = $request->user()->wishlistItems()
            ->with([
                'product.author.defaultUrl',
                'product.defaultUrl',
                'product.media',
                'product.variants.prices.currency',
                'product.variants.prices.priceable',
            ])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('storefront.account.wishlist', ['items' => $items]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $productId = Url::query()
            ->where('slug', $slug)
            ->where('element_type', (new Product)->getMorphClass())
            ->value('element_id');
        abort_unless($productId !== null, 404);

        $product = Product::published()->whereKey($productId)->firstOrFail();

        $request->user()->wishlistItems()->firstOrCreate([
            'product_id' => $product->getKey(),
        ]);

        return redirect()->route('products.show', $product)->with('status', 'Added to your wishlist.');
    }

    public function destroy(Request $request, WishlistItem $wishlistItem): RedirectResponse
    {
        abort_unless($wishlistItem->user_id === $request->user()->getKey(), 404);

        $wishlistItem->delete();

        return back()->with('status', 'Removed from your wishlist.');
    }
}
