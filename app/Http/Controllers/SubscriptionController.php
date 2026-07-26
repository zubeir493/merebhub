<?php

namespace App\Http\Controllers;

use App\Enums\BillingModel;
use App\Enums\SubscriptionStatus;
use App\Models\CartItem;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        return view('storefront.subscriptions', [
            'subscriptions' => $request->user()
                ->subscriptions()
                ->with(['product', 'productPlan'])
                ->latest('expires_at')
                ->get(),
            'title' => 'Subscriptions',
        ]);
    }

    public function renew(Request $request, Subscription $subscription): RedirectResponse
    {
        abort_unless($subscription->customer_id === $request->user()->id, 404);
        abort_unless(in_array($subscription->status, [
            SubscriptionStatus::Active,
            SubscriptionStatus::Expired,
        ], true), 422);
        $plan = $subscription->productPlan;
        abort_unless($plan->is_active && $plan->billing_model === BillingModel::ManualSubscription, 422);

        CartItem::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'product_plan_id' => $plan->id,
            ],
            [
                'product_id' => $subscription->product_id,
                'renewal_subscription_id' => $subscription->id,
                'quantity' => 1,
            ],
        );

        return redirect()->route('cart.index')->with('status', 'Renewal added to your cart.');
    }
}
