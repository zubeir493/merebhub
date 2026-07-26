<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWishlistItemRequest;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        return view('storefront.wishlist', [
            'items' => $request->user()
                ? $request->user()->wishlistItems()->with('product.author')->latest()->get()
                : collect(),
        ]);
    }

    public function store(StoreWishlistItemRequest $request, Product $product): RedirectResponse
    {
        WishlistItem::firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return back()->with('status', "{$product->name} was added to your wishlist.");
    }

    public function destroy(Request $request, WishlistItem $wishlistItem): RedirectResponse
    {
        abort_unless($wishlistItem->user_id === $request->user()?->id, 404);
        $wishlistItem->delete();

        return back()->with('status', 'Item removed from your wishlist.');
    }
}
