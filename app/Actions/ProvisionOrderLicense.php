<?php

namespace App\Actions;

use App\Contracts\LicensingProvider;
use App\Enums\FulfillmentType;
use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Mail\PurchaseConfirmation;
use App\Models\License;
use App\Models\Order;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class ProvisionOrderLicense
{
    public function __construct(private LicensingProvider $licensing) {}

    public function handle(Order $order): ?License
    {
        $order->loadMissing(['items.product.versions', 'items.productPlan', 'items.license', 'licenses']);

        if ($order->status !== OrderStatus::Paid) {
            throw new RuntimeException('Only paid orders can be fulfilled.');
        }

        $licenseItems = $order->items->filter(
            fn ($item): bool => $item->fulfillment_type === FulfillmentType::LicenseKey,
        );
        $licenses = collect();

        if ($licenseItems->isEmpty()) {
            return null;
        }

        foreach ($licenseItems as $item) {
            if ($item->license) {
                $licenses->push($item->license);

                continue;
            }

            $licenseData = $this->licensing->createLicense($item);

            if (blank($licenseData['id']) || blank($licenseData['key'])) {
                throw new RuntimeException('The licensing provider returned an incomplete response.');
            }

            $license = DB::transaction(fn () => License::firstOrCreate(
                ['order_item_id' => $item->id],
                [
                    'marketplace_license_id' => (string) Str::uuid(),
                    'order_id' => $order->id,
                    'customer_id' => $order->buyer_user_id,
                    'product_id' => $item->product_id,
                    'product_plan_id' => $item->product_plan_id,
                    'buyer_email' => $order->buyer_email,
                    'provider' => 'keygen',
                    'provider_policy_id' => $item->license_configuration['keygen_policy_id'] ?? null,
                    'provider_license_id' => $licenseData['id'],
                    'keygen_license_id' => $licenseData['id'],
                    'license_key' => $licenseData['key'],
                    'status' => LicenseStatus::Active,
                    'activation_limit' => $item->license_configuration['activation_limit'] ?? 1,
                    'activation_count' => 0,
                    'issued_at' => now(),
                    'expires_at' => $licenseData['expires_at'],
                ],
            ));

            if ($license->wasRecentlyCreated) {
                Mail::to($order->buyer_email)->send(new PurchaseConfirmation($license));
            }

            Subscription::where('order_item_id', $item->id)
                ->whereNull('license_id')
                ->update(['license_id' => $license->id]);

            $licenses->push($license);
        }

        $order->update([
            'status' => $licenseItems->count() === $licenses->count()
                ? OrderStatus::Fulfilled
                : OrderStatus::PartiallyFulfilled,
            'fulfillment_error' => null,
        ]);

        return $licenses->first();
    }
}
