<?php

namespace App\Http\Controllers;

use App\Domain\Fulfillment\Enums\AssetScanStatus;
use App\Http\Requests\AccountSettingsRequest;
use App\Models\DownloadableAsset;
use App\Models\Entitlement;
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
            ->with('productLines.purchasable.product')
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
                'orderLine.purchasable.product',
                'fulfillmentUnit',
                'product.downloadableAssets' => fn (HasMany $query) => $query->where('scan_status', 'clean'),
            ])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('storefront.account.purchases', ['purchases' => $purchases]);
    }

    public function downloads(Request $request): View
    {
        $entitledProductIds = Entitlement::query()
            ->whereBelongsTo($request->user())
            ->where('status', 'active')
            ->whereNotNull('product_id')
            ->select('product_id');
        $downloads = DownloadableAsset::query()
            ->where('scan_status', AssetScanStatus::Clean)
            ->whereIn('product_id', $entitledProductIds)
            ->with('product.defaultUrl')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('storefront.account.downloads', ['downloads' => $downloads]);
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

        $emailChanged = $data['email'] !== $user->email;

        $user->fill($data);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'Account settings updated.');
    }
}
