<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL as URLFacade;
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

    public function mini(): JsonResponse
    {
        $cart = CartSession::current();

        return response()->json($this->miniCartPayload($cart));
    }

    public function share(): JsonResponse
    {
        $cart = CartSession::current();
        $cart?->loadMissing('lines');
        $payload = ($cart?->lines ?? collect())->map(fn (CartLine $item): array => [
            'variant_id' => (int) $item->purchasable_id,
            'quantity' => min(10, max(1, (int) $item->quantity)),
        ])->values()->all();

        if ($payload === []) {
            return response()->json(['message' => 'Add an item before sharing your cart.'], 422);
        }

        $encoded = rtrim(strtr(base64_encode(json_encode($payload, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
        $expiresAt = now()->addDays(7);

        return response()->json([
            'url' => URLFacade::temporarySignedRoute('cart.shared', $expiresAt, ['payload' => $encoded]),
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function shared(Request $request): RedirectResponse
    {
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

        $variantIds = collect($items)
            ->filter(fn (mixed $item): bool => is_array($item) && isset($item['variant_id']))
            ->pluck('variant_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->take(100);
        $variants = ProductVariant::query()->whereIn('id', $variantIds)->get()->keyBy('id');
        $publishedProductIds = Product::published()
            ->whereKey($variants->pluck('product_id'))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
        $addedQuantity = 0;

        foreach (array_slice($items, 0, 100) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $variant = $variants->get((int) ($item['variant_id'] ?? 0));
            $quantity = min(10, max(1, (int) ($item['quantity'] ?? 1)));

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

    /**
     * @return array{cart_count: int, items: array<int, array{id: int, name: string, option: string, quantity: int, total: string, image: ?string, url: string, unit_price: string}>, total: string}
     */
    private function miniCartPayload(mixed $cart): array
    {
        if ($cart === null) {
            return [
                'cart_count' => 0,
                'items' => [],
                'total' => '0.00 ETB',
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
            'total' => $cart->total->format(),
        ];
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
