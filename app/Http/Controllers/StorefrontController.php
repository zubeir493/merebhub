<?php

namespace App\Http\Controllers;

use App\Enums\AppSubmissionStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Http\Requests\LookupOrderRequest;
use App\Http\Requests\StartCheckoutRequest;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\AppSubmission;
use App\Models\Author;
use App\Models\Order;
use App\Models\Product;
use App\Services\WooCommerceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Throwable;

class StorefrontController extends Controller
{
    public function home(): View
    {
        return view('storefront.home');
    }

    public function product(Product $product): View
    {
        abort_unless($product->status === ProductStatus::Published, 404);

        return view('storefront.product', [
            'product' => $product->load(['author', 'platforms', 'versions']),
            'relatedProducts' => Product::published()
                ->with('author')
                ->where('category', $product->category)
                ->whereKeyNot($product)
                ->take(4)
                ->get(),
        ]);
    }

    public function author(Author $author): View
    {
        abort_unless($author->is_public, 404);

        return view('storefront.author', [
            'author' => $author->load(['products' => fn ($query) => $query->where('status', ProductStatus::Published)->with('platforms')]),
        ]);
    }

    public function submit(): View
    {
        return view('storefront.submit');
    }

    public function storeSubmission(StoreSubmissionRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('build');
        $data['file_path'] = $request->file('build')->store(
            'submissions',
            config('filesystems.builds_disk'),
        );

        AppSubmission::create($data + [
            'status' => AppSubmissionStatus::Pending,
        ]);

        return back()->with('status', 'Submission received. An admin will review it.');
    }

    public function checkout(Product $product): View
    {
        abort_unless($product->status === ProductStatus::Published, 404);

        return view('storefront.checkout', [
            'product' => $product->load('author'),
            'buyerEmail' => Auth::user()?->email,
        ]);
    }

    public function startCheckout(StartCheckoutRequest $request, Product $product, WooCommerceService $woocommerce): RedirectResponse
    {
        abort_unless($product->status === ProductStatus::Published, 404);
        abort_unless($product->wc_product_id, 422, 'This product is not connected to WooCommerce yet.');

        try {
            $wcOrder = $woocommerce->createOrder($product, $request->string('buyer_email'));
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['checkout' => 'Checkout is temporarily unavailable. Please try again shortly.']);
        }

        Order::updateOrCreate(
            ['wc_order_id' => $wcOrder['id']],
            [
                'buyer_email' => $request->string('buyer_email'),
                'buyer_user_id' => Auth::id(),
                'product_id' => $product->id,
                'amount' => $product->price,
                'currency' => $wcOrder['currency'] ?? 'ETB',
                'status' => OrderStatus::Pending,
            ]
        );

        return redirect()->away($woocommerce->checkoutUrl($wcOrder));
    }

    public function purchases(): View
    {
        $user = Auth::user();
        $orders = Order::query()
            ->with(['product.versions', 'license'])
            ->where(fn ($query) => $query
                ->where('buyer_user_id', $user?->id)
                ->orWhere('buyer_email', $user?->email))
            ->latest()
            ->get();

        return view('storefront.purchases', [
            'orders' => $orders,
            'downloadUrls' => $orders->mapWithKeys(function (Order $order): array {
                $version = $order->product->versions->sortByDesc('created_at')->first();

                return [$order->id => $version && $order->license
                    ? URL::temporarySignedRoute('downloads.show', now()->addMinutes(15), [
                        'version' => $version,
                        'license' => $order->license,
                    ])
                    : null];
            }),
        ]);
    }

    public function lookup(): View
    {
        return view('storefront.lookup');
    }

    public function lookupResult(LookupOrderRequest $request): View
    {
        $order = Order::query()
            ->with(['product.versions', 'license'])
            ->where('buyer_email', $request->string('buyer_email'))
            ->where('wc_order_id', $request->integer('wc_order_id'))
            ->first();
        $version = $order?->product->versions->sortByDesc('created_at')->first();

        return view('storefront.lookup-result', [
            'order' => $order,
            'downloadUrl' => $version && $order?->license
                ? URL::temporarySignedRoute('downloads.show', now()->addMinutes(15), [
                    'version' => $version,
                    'license' => $order->license,
                ])
                : null,
        ]);
    }
}
