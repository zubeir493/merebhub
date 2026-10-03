<?php

namespace App\Http\Controllers;

use App\Domain\Fulfillment\Enums\AssetScanStatus;
use App\Http\Requests\AccountSettingsRequest;
use App\Integrations\Keygen\KeygenClient;
use App\Models\DownloadableAsset;
use App\Models\Entitlement;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

class AccountController extends Controller
{
    public function __construct(private readonly KeygenClient $keygen) {}

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
                'orderLine.purchasable.values',
                'fulfillmentUnit',
                'product.downloadableAssets' => fn (HasMany $query) => $query->where('scan_status', 'clean'),
            ])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $deviceUsage = $purchases->getCollection()
            ->mapWithKeys(fn (Entitlement $purchase): array => [$purchase->getKey() => $this->deviceUsage($purchase)])
            ->all();

        return view('storefront.account.purchases', [
            'purchases' => $purchases,
            'deviceUsage' => $deviceUsage,
        ]);
    }

    /**
     * @return array{used: int, limit: int|null}|null
     */
    private function deviceUsage(Entitlement $purchase): ?array
    {
        if (config('marketplace.fulfillment.license_provider') !== 'keygen' || blank($purchase->external_id)) {
            return null;
        }

        try {
            $license = Cache::remember(
                'keygen-license-usage:'.$purchase->external_id,
                now()->addSeconds(30),
                fn (): array => $this->keygen->license((string) $purchase->external_id),
            );
        } catch (Throwable) {
            return null;
        }

        $attributes = data_get($license, 'data.attributes');
        $machineMeta = data_get($license, 'data.relationships.machines.meta');

        if (! is_array($attributes) || ! array_key_exists('maxMachines', $attributes) || ! is_array($machineMeta)) {
            return null;
        }

        $used = $machineMeta['count'] ?? null;
        $limit = $attributes['maxMachines'];

        if (! is_numeric($used) || ($limit !== null && ! is_numeric($limit))) {
            return null;
        }

        return [
            'used' => (int) $used,
            'limit' => $limit === null ? null : (int) $limit,
        ];
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
