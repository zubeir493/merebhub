<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Lunar\Core\Exceptions\Carts\CartException;
use Lunar\Core\Facades\CartSession;
use Lunar\Core\Models\CartLine;
use Lunar\Core\Models\ProductVariant;
use Lunar\Core\Models\Url;

class CartController extends Controller
{
    public function index(): View
    {
        $cart = CartSession::current();
        $cart?->loadMissing('lines.purchasable.values');
        $items = $cart?->lines ?? collect();
        $productIds = $items->pluck('purchasable.product_id')->filter()->unique();
        $products = Product::query()
            ->with(['author', 'defaultUrl', 'media'])
            ->whereKey($productIds)
            ->get()
            ->keyBy('id');

        $items->each(function (CartLine $item) use ($products): void {
            $variant = $item->purchasable;

            if ($variant && $products->has($variant->product_id)) {
                $variant->setRelation('product', $products->get($variant->product_id));
            }
        });

        return view('storefront.cart', [
            'cart' => $cart,
            'items' => $items,
            'subtotalMinor' => $cart?->subTotal?->value ?? 0,
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:lunar_product_variants,id'],
        ]);
        $productId = Url::query()
            ->where('slug', $slug)
            ->where('element_type', (new Product)->getMorphClass())
            ->value('element_id');
        abort_unless($productId !== null, 404);

        $product = Product::published()->whereKey($productId)->firstOrFail();
        $variant = ProductVariant::query()
            ->whereKey($validated['variant_id'])
            ->whereBelongsTo($product)
            ->firstOrFail();

        try {
            CartSession::add($variant, 1);
        } catch (CartException $exception) {
            return back()->withErrors(['cart' => $exception->getMessage()]);
        }

        return redirect()->route('cart.index')->with('status', 'Added to your cart.');
    }

    public function update(Request $request, int $cartLine): RedirectResponse
    {
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:10']]);
        CartSession::updateLine($cartLine, $validated['quantity']);

        return back()->with('status', 'Cart updated.');
    }

    public function destroy(int $cartLine): RedirectResponse
    {
        CartSession::remove($cartLine);

        return back()->with('status', 'Item removed from your cart.');
    }
}
