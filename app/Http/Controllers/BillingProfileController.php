<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveBillingProfileRequest;
use App\Models\BillingProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Lunar\Core\Models\Country;

class BillingProfileController extends Controller
{
    public function edit(Request $request): View
    {
        Gate::forUser($request->user())->authorize('viewAny', BillingProfile::class);

        return view('storefront.account.billing', [
            'profile' => $request->user()->billingProfile()->first(),
            'countries' => Country::query()->orderBy('name')->get(['iso3', 'name']),
        ]);
    }

    public function update(SaveBillingProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->billingProfile()->first();

        Gate::forUser($user)->authorize(
            $profile ? 'update' : 'create',
            $profile ?? BillingProfile::class,
        );

        $user->billingProfile()->updateOrCreate([], $request->validated());

        return back()->with('status', 'Billing details saved.');
    }
}
