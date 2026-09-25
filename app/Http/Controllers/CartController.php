<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Lunar\Core\Exceptions\Carts\CartException;
use Lunar\Core\Facades\CartSession;
use Lunar\Core\Facades\Discounts;
use Lunar\Core\Models\CartLine;
use Lunar\Core\Models\ProductVariant;
use Lunar\Core\Models\Url;

class CartController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('mh-cart-share')) {
            return $this->restoreFromShareCode((string) $request->query('mh-cart-share'));
        }

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

    public function mini(): JsonResponse
    {
        $cart = CartSession::current();

        return response()->json($this->miniCartPayload($cart));
    }

    public function share(): JsonResponse
    {
        $cart = CartSession::current();
        $cart?->loadMissing('lines');
        $lines = $cart?->lines ?? collect();

        if ($lines->isEmpty()) {
            return response()->json(['message' => 'Add an item before sharing your cart.'], 422);
        }

        $code = $lines->map(function (CartLine $item): string {
            $qty = min(10, max(1, (int) $item->quantity));

            return $qty > 1 ? "{$item->purchasable_id}:{$qty}" : (string) $item->purchasable_id;
        })->join(':');

        return response()->json([
            'url' => url('/?mh-cart-share='.$code),
            'code' => $code,
        ]);
    }

    public function shared(Request $request): RedirectResponse
    {
        if ($request->filled('mh-cart-share')) {
            return $this->restoreFromShareCode((string) $request->query('mh-cart-share'));
        }

        $encoded = (string) $request->query('payload');
        $encoded .= str_repeat('=', (4 - strlen($encoded) % 4) % 4);
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);

        if (! is_string($decoded)) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'This shared cart link is invalid.']);
        }

        try {
            $items = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'This shared cart link is invalid.']);
        }

        if (! is_array($items)) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'This shared cart link is invalid.']);
        }

        $tokens = [];
        foreach ($items as $item) {
            if (is_array($item) && isset($item['variant_id'])) {
                $qty = min(10, max(1, (int) ($item['quantity'] ?? 1)));
                $tokens[] = "{$item['variant_id']}:{$qty}";
            }
        }

        return $this->restoreFromShareCode(implode(':', $tokens));
    }

    public function restoreFromShareCode(string $code): RedirectResponse
    {
        $tokens = array_values(array_filter(
            preg_split('/[:,-]/', $code) ?: [],
            fn (string $t): bool => is_numeric($t) && (int) $t > 0
        ));

        if (empty($tokens)) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'This shared cart link is invalid.']);
        }

        $tokenInts = array_map('intval', $tokens);
        $variants = ProductVariant::query()->whereIn('id', $tokenInts)->get()->keyBy('id');
        $publishedProductIds = Product::published()
            ->whereKey($variants->pluck('product_id'))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $items = [];
        $i = 0;
        $count = count($tokens);

        while ($i < $count) {
            $id = (int) $tokens[$i];
            if (! isset($variants[$id])) {
                $i++;

                continue;
            }

            $next = ($i + 1 < $count) ? (int) $tokens[$i + 1] : null;
            if ($next !== null && $next >= 1 && $next <= 10 && (! isset($variants[$next]) || ($i + 2 < $count && isset($variants[(int) $tokens[$i + 2]])))) {
                $items[$id] = $next;
                $i += 2;
            } else {
                $items[$id] = 1;
                $i += 1;
            }
        }

        $addedQuantity = 0;

        foreach ($items as $variantId => $quantity) {
            $variant = $variants->get($variantId);
            if ($variant === null || ! in_array((int) $variant->product_id, $publishedProductIds, true)) {
                continue;
            }

            try {
                CartSession::add($variant, $quantity);
                $addedQuantity += $quantity;
            } catch (CartException) {
                continue;
            }
        }

        if ($addedQuantity === 0) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'We could not add the shared cart items.']);
        }

        $itemLabel = $addedQuantity === 1 ? 'item' : 'items';

        return redirect()->route('cart.index')->with('status', $addedQuantity.' shared '.$itemLabel.' added to your cart.');
    }

    public function store(Request $request, string $slug): RedirectResponse|JsonResponse
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
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withErrors(['cart' => $exception->getMessage()]);
        }

        $message = 'Added to your cart.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                ...$this->miniCartPayload(CartSession::current()),
            ]);
        }

        return redirect()->route('products.show', $product)->with('status', $message);
    }

    public function applyCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'coupon_code' => ['required', 'string', 'max:50'],
        ]);

        $code = strtoupper(trim((string) $validated['coupon_code']));
        $cart = CartSession::current();

        if (! $cart || $cart->lines->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your cart is empty.'], 422);
            }

            return back()->withErrors(['coupon_code' => 'Your cart is empty.']);
        }

        /** @var Coupon|null $coupon */
        $coupon = Coupon::query()->where('coupon', $code)->first();

        if (! $coupon) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Invalid discount code.'], 422);
            }

            return back()->withErrors(['coupon_code' => 'Invalid discount code.']);
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            $msg = 'This coupon is not active yet.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->withErrors(['coupon_code' => $msg]);
        }

        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            $msg = 'This coupon has expired.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->withErrors(['coupon_code' => $msg]);
        }

        if ($coupon->max_uses !== null && $coupon->uses >= $coupon->max_uses) {
            $msg = 'This coupon has reached its maximum redemption limit.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->withErrors(['coupon_code' => $msg]);
        }

        if ($coupon->max_uses_per_user !== null && $user = auth()->user()) {
            $userUses = $coupon->users()->whereKey($user->getKey())->count();
            if ($userUses >= $coupon->max_uses_per_user) {
                $msg = 'You have already redeemed this coupon the maximum allowed times.';
                if ($request->expectsJson()) {
                    return response()->json(['message' => $msg], 422);
                }

                return back()->withErrors(['coupon_code' => $msg]);
            }
        }

        $currencyCode = $cart->currency?->code ?? 'ETB';
        $minSpendInCents = $coupon->data['min_prices'][$currencyCode] ?? null;
        if ($minSpendInCents !== null && $cart->subTotal->value < $minSpendInCents) {
            $formattedMin = number_format($minSpendInCents / 100, 2, '.', ',');
            $msg = "A minimum spend of {$formattedMin} {$currencyCode} is required to use this coupon.";
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->withErrors(['coupon_code' => $msg]);
        }

        Discounts::resetDiscounts();
        $cart->coupon_code = $code;
        $cart->recalculate();
        $cart->save();

        if (! $cart->discounts || $cart->discounts->isEmpty() || ($cart->discountTotal?->value ?? 0) <= 0) {
            $cart->coupon_code = null;
            Discounts::resetDiscounts();
            $cart->recalculate();
            $cart->save();

            $msg = 'This coupon cannot be applied to the items in your cart.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->withErrors(['coupon_code' => $msg]);
        }

        $msg = "Coupon '{$code}' applied successfully!";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $msg,
                ...$this->miniCartPayload($cart),
            ]);
        }

        return redirect()->route('cart.index')->with('status', $msg);
    }

    public function removeCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $cart = CartSession::current();

        if ($cart) {
            Discounts::resetDiscounts();
            $cart->coupon_code = null;
            $cart->recalculate();
            $cart->save();
        }

        $msg = 'Coupon removed from your cart.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $msg,
                ...$this->miniCartPayload($cart),
            ]);
        }

        return redirect()->route('cart.index')->with('status', $msg);
    }

    /**
     * @return array{cart_count: int, items: array<int, array{id: int, name: string, option: string, quantity: int, total: string, image: ?string, url: string, unit_price: string}>, total: string, subtotal: string, discount_total: ?string, coupon_code: ?string}
     */
    private function miniCartPayload(mixed $cart): array
    {
        if ($cart === null) {
            return [
                'cart_count' => 0,
                'items' => [],
                'total' => '0.00 ETB',
                'subtotal' => '0.00 ETB',
                'discount_total' => null,
                'coupon_code' => null,
            ];
        }

        $cart->loadMissing('lines.purchasable.values');
        $items = $cart->lines;
        $productIds = $items->pluck('purchasable.product_id')->filter()->unique();
        $products = Product::query()
            ->with(['defaultUrl', 'media'])
            ->whereKey($productIds)
            ->get()
            ->keyBy('id');

        $discountValue = $cart->discountTotal?->value ?? 0;

        return [
            'cart_count' => (int) $items->sum('quantity'),
            'items' => $items->map(function (CartLine $item) use ($products): array {
                $variant = $item->purchasable;
                $product = $products->get($variant?->product_id);

                return [
                    'id' => (int) $item->getKey(),
                    'name' => (string) ($product?->name ?: $variant?->getDescription() ?: 'Product'),
                    'option' => $product instanceof Product && $variant instanceof ProductVariant
                        ? $product->variantDisplayName($variant)
                        : 'Standard license',
                    'quantity' => (int) $item->quantity,
                    'total' => $item->total->format(),
                    'image' => $product?->coverUrl(),
                    'url' => $product ? route('products.show', $product) : route('cart.index'),
                    'unit_price' => $item->unitPrice->format(),
                ];
            })->values()->all(),
            'total' => $cart->total?->format() ?? '0.00 ETB',
            'subtotal' => $cart->subTotal?->format() ?? $cart->total?->format() ?? '0.00 ETB',
            'discount_total' => $discountValue > 0 ? $cart->discountTotal?->format() : null,
            'coupon_code' => $cart->coupon_code,
        ];
    }

    public function update(Request $request, CartLine|int $cartLine): RedirectResponse|JsonResponse
    {
        $lineId = $cartLine instanceof CartLine ? (int) $cartLine->getKey() : (int) $cartLine;
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:10']]);
        CartSession::updateLine($lineId, $validated['quantity']);

        $cart = CartSession::current();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Cart updated.',
                ...$this->miniCartPayload($cart),
            ]);
        }

        return back()->with('status', 'Cart updated.');
    }

    public function destroy(Request $request, CartLine|int $cartLine): RedirectResponse|JsonResponse
    {
        $lineId = $cartLine instanceof CartLine ? (int) $cartLine->getKey() : (int) $cartLine;
        CartSession::remove($lineId);

        $cart = CartSession::current();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Item removed from your cart.',
                ...$this->miniCartPayload($cart),
            ]);
        }

        return back()->with('status', 'Item removed from your cart.');
    }
}
