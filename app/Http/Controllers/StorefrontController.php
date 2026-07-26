<?php

namespace App\Http\Controllers;

use App\Enums\AppSubmissionStatus;
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
