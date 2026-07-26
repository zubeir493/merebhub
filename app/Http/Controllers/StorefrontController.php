<?php

namespace App\Http\Controllers;

use App\Enums\AppSubmissionStatus;
use App\Enums\AuthorStatus;
use App\Enums\BillingInterval;
use App\Enums\BillingModel;
use App\Enums\FulfillmentType;
use App\Enums\ProductStatus;
use App\Http\Requests\LookupOrderRequest;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\AppSubmission;
use App\Models\Author;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

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
            'product' => $product->load(['author', 'platforms', 'versions', 'publicContributors', 'activePlans']),
            'relatedProducts' => Product::published()
                ->with('author')
                ->where('category', $product->category)
                ->whereKeyNot($product)
                ->take(4)
                ->get(),
        ]);
    }

    public function vendors(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $sort = $request->string('sort', 'newest')->toString();
        $vendors = Author::query()
            ->publiclyVisible()
            ->withCount(['products' => fn ($query) => $query->published()])
            ->when($search !== '', fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('tagline', 'like', "%{$search}%"),
            ))
            ->when($request->boolean('verified'), fn ($query) => $query->where('is_verified', true))
            ->when($request->boolean('featured'), fn ($query) => $query->where('is_featured', true))
            ->when($sort === 'products', fn ($query) => $query->orderByDesc('products_count'))
            ->when($sort === 'sales', fn ($query) => $query->orderByDesc('public_sales_count'))
            ->when($sort === 'rating', fn ($query) => $query->orderByDesc('average_rating'))
            ->when($sort === 'newest', fn ($query) => $query->latest())
            ->paginate(12)
            ->withQueryString();

        return view('storefront.vendors', [
            'vendors' => $vendors,
            'search' => $search,
            'sort' => $sort,
            'title' => 'Ethiopian software vendors',
        ]);
    }

    public function vendor(Request $request, Author $author): View
    {
        abort_unless($author->status === AuthorStatus::Active && $author->is_public, 404);

        $search = $request->string('q')->trim()->toString();
        $category = $request->string('category')->trim()->toString();
        $sort = $request->string('sort', 'newest')->toString();
        $vendorProducts = fn () => Product::query()
            ->published()
            ->where(fn ($query) => $query
                ->whereBelongsTo($author, 'author')
                ->orWhereHas('authors', fn ($query) => $query->whereKey($author->getKey())));
        $products = $vendorProducts()
            ->with(['author', 'platforms', 'publicContributors'])
            ->when($search !== '', fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('tagline', 'like', "%{$search}%"),
            ))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($sort === 'popular', fn ($query) => $query->orderByDesc('weekly_sales'))
            ->when($sort === 'rating', fn ($query) => $query->orderByDesc('rating'))
            ->when($sort === 'price', fn ($query) => $query->orderBy('price'))
            ->when($sort === 'newest', fn ($query) => $query->latest())
            ->paginate(12)
            ->withQueryString();

        return view('storefront.author', [
            'author' => $author,
            'products' => $products,
            'categories' => $vendorProducts()->distinct()->orderBy('category')->pluck('category'),
            'search' => $search,
            'category' => $category,
            'sort' => $sort,
            'title' => $author->name,
        ]);
    }

    public function submit(): View
    {
        return view('storefront.submit', [
            'billingModels' => BillingModel::cases(),
            'billingIntervals' => BillingInterval::cases(),
            'fulfillmentTypes' => FulfillmentType::cases(),
        ]);
    }

    public function storeSubmission(StoreSubmissionRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('attachments');

        DB::transaction(function () use ($data, $request): void {
            $submission = AppSubmission::create($data + [
                'status' => AppSubmissionStatus::Pending,
            ]);

            foreach ($request->file('attachments', []) as $attachment) {
                $submission->attachments()->create([
                    'path' => $attachment->store('submissions', config('filesystems.builds_disk')),
                    'original_name' => $attachment->getClientOriginalName(),
                    'mime_type' => $attachment->getMimeType() ?: 'application/octet-stream',
                    'size' => $attachment->getSize(),
                ]);
            }
        });

        return back()->with('status', 'Submission received. An admin will review it.');
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
            ->where('public_id', $request->string('order_reference'))
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
