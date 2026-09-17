<?php

namespace App\Domain\Billing\Actions;

use App\Models\InvoiceSnapshot;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Lunar\Core\Models\Order;
use Lunar\Core\Models\ProductVariant;

class EnsureInvoiceSnapshotAction
{
    public function handle(Order $order): InvoiceSnapshot
    {
        return DB::transaction(function () use ($order): InvoiceSnapshot {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $snapshot = InvoiceSnapshot::query()->where('order_id', $lockedOrder->getKey())->first();

            if ($snapshot) {
                return $snapshot;
            }

            if ($lockedOrder->placed_at === null || $lockedOrder->user_id === null) {
                throw new LogicException('Only a placed customer order can have an invoice snapshot.');
            }

            $lockedOrder->loadMissing(['billingAddress.country', 'currency', 'lines.purchasable.product', 'user']);
            $currency = $lockedOrder->currency;
            $billingAddress = $lockedOrder->billingAddress;
            $name = Str::of((string) ($lockedOrder->user?->name ?? ''))->squish();
            $firstName = $name->before(' ')->toString();
            $lastName = $name->contains(' ') ? $name->after(' ')->toString() : '';
            $lineItems = $lockedOrder->lines->map(function ($line): array {
                $variant = $line->purchasable;

                return [
                    'description' => (string) $line->description,
                    'option' => $variant instanceof ProductVariant
                        ? Product::displayVariantName($variant)
                        : $line->option,
                    'identifier' => $line->identifier,
                    'quantity' => (int) $line->quantity,
                    'unit_price' => (int) $line->unit_price,
                    'subtotal' => (int) $line->sub_total,
                    'discount_total' => (int) $line->discount_total,
                    'tax_total' => (int) $line->tax_total,
                    'total' => (int) $line->total,
                ];
            })->values()->all();

            if ($lineItems === []) {
                throw new LogicException('An invoice snapshot requires at least one order line.');
            }

            return InvoiceSnapshot::query()->create([
                'user_id' => $lockedOrder->user_id,
                'order_id' => $lockedOrder->getKey(),
                'invoice_number' => sprintf(
                    'MH-%s-%08d',
                    $lockedOrder->placed_at->format('Y'),
                    $lockedOrder->getKey(),
                ),
                'currency_code' => strtoupper((string) $lockedOrder->currency_code),
                'currency_factor' => max(1, (int) ($currency?->factor ?? 100)),
                'currency_decimal_places' => max(0, (int) ($currency?->decimal_places ?? 2)),
                'payment_status' => (string) $lockedOrder->getRawOriginal('payment_status'),
                'subtotal' => (int) $lockedOrder->sub_total,
                'discount_total' => (int) $lockedOrder->discount_total,
                'tax_total' => (int) $lockedOrder->tax_total,
                'shipping_total' => (int) $lockedOrder->shipping_total,
                'total' => (int) $lockedOrder->total,
                'billing_snapshot' => [
                    'first_name' => $billingAddress?->first_name ?: $firstName,
                    'last_name' => $billingAddress?->last_name ?: $lastName,
                    'company_name' => $billingAddress?->company_name,
                    'tax_identifier' => $billingAddress?->tax_identifier,
                    'contact_email' => $billingAddress?->contact_email ?? $lockedOrder->user?->email,
                    'contact_phone' => $billingAddress?->contact_phone,
                    'line_one' => $billingAddress?->line_one,
                    'line_two' => $billingAddress?->line_two,
                    'line_three' => $billingAddress?->line_three,
                    'city' => $billingAddress?->city,
                    'state' => $billingAddress?->state,
                    'postcode' => $billingAddress?->postcode,
                    'country_iso3' => $billingAddress?->country?->iso3,
                    'country_name' => $billingAddress?->country?->name,
                ],
                'line_items' => $lineItems,
                'issued_at' => $lockedOrder->placed_at,
            ]);
        });
    }
}
