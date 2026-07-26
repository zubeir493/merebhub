<?php

namespace App\Http\Controllers;

use App\Actions\CreateWooCommerceCheckout;
use App\Enums\ProductStatus;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $items = $request->user()
            ? $request->user()->cartItems()->with('product.author')->latest()->get()
            : collect();

        return view('storefront.cart', [
            'items' => $items,
            'subtotal' => $items->sum(fn (CartItem $item): float => (float) $item->product->price * $item->quantity),
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === ProductStatus::Published, 404);

        if (! Auth::check()) {
            $request->session()->put('pending_cart_product_id', $product->id);
            $request->session()->put('url.intended', route('cart.index'));

            return redirect()->route('login')->with('status', 'Log in to add this app to your cart.');
        }

        CartItem::firstOrCreate(
            ['user_id' => $request->user()->id, 'product_id' => $product->id],
            ['quantity' => 1],
        );

        return redirect()->route('cart.index')->with('status', "{$product->name} was added to your cart.");
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->user_id === $request->user()->id, 404);
        $cartItem->update($request->validated());

        return back()->with('status', 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->user_id === $request->user()?->id, 404);
        $cartItem->delete();

        return back()->with('status', 'Item removed from your cart.');
    }

    public function checkout(Request $request, CreateWooCommerceCheckout $checkout): RedirectResponse
    {
        try {
            $order = $checkout->handle($request->user());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['checkout' => $exception->getMessage()]);
        }

        return redirect()->away((string) $order->payment_url);
    }
}
