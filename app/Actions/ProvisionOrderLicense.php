<?php

namespace App\Actions;

use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Mail\PurchaseConfirmation;
use App\Models\License;
use App\Models\Order;
use App\Services\KeygenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class ProvisionOrderLicense
{
    public function __construct(private KeygenService $keygen) {}

    public function handle(Order $order): License
    {
        $order->loadMissing(['product.versions', 'license']);

        if ($order->status !== OrderStatus::Paid) {
            throw new RuntimeException('Only paid orders can be fulfilled.');
        }

        if ($order->license) {
            return $order->license;
        }

        $response = $this->keygen->createLicense($order, $order->product);
        $licenseData = $this->keygen->licenseData($response);

        if (blank($licenseData['id']) || blank($licenseData['key'])) {
            throw new RuntimeException('Keygen returned an incomplete license response.');
        }

        $license = DB::transaction(fn () => License::firstOrCreate(
            ['order_id' => $order->id],
            [
                'product_id' => $order->product_id,
                'buyer_email' => $order->buyer_email,
                'keygen_license_id' => $licenseData['id'],
                'license_key' => $licenseData['key'],
                'status' => LicenseStatus::Active,
                'activation_limit' => 1,
                'expires_at' => $licenseData['expires_at'],
            ],
        ));

        $order->update(['fulfillment_error' => null]);

        Mail::to($order->buyer_email)->send(new PurchaseConfirmation($license));

        return $license;
    }
}
