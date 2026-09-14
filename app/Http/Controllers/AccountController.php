<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountSettingsRequest;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function orders(Request $request): View
    {
        $orders = $request->user()->orders()
            ->whereNotNull('placed_at')
            ->with('productLines')
            ->latest('placed_at')
            ->get();

        return view('storefront.account.orders', ['orders' => $orders]);
    }

    public function purchases(Request $request): View
    {
        $purchases = $request->user()->entitlements()
            ->with([
                'credential',
                'order',
                'product.downloadableAssets' => fn (HasMany $query) => $query->where('scan_status', 'clean'),
            ])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('storefront.account.purchases', ['purchases' => $purchases]);
    }

    public function settings(Request $request): View
    {
        return view('storefront.account.settings', ['user' => $request->user()]);
    }

    public function update(AccountSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'email', 'password']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        if ($data['email'] !== $user->email) {
            $data['email_verified_at'] = null;
        }

        $user->update($data);

        return back()->with('status', 'Account settings updated.');
    }
}
