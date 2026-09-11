<?php

namespace App\Domain\Fulfillment\Actions;

use App\Models\FulfillmentUnit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lunar\Core\Models\Order;
use Lunar\Core\Models\OrderLine;
use Lunar\Core\Models\Product;

class CreateFulfillmentUnitsAction
{
    /**
     * @return Collection<int, FulfillmentUnit>
     */
    public function handle(Order $order): Collection
    {
        $order->loadMissing(['lines.purchasable.product', 'user']);

        return DB::transaction(function () use ($order): Collection {
            foreach ($order->lines as $line) {
                if (! $line instanceof OrderLine || $line->type === 'shipping') {
                    continue;
                }

                $product = $line->purchasable?->product;

                if (! $product instanceof Product) {
                    continue;
                }

                $summary = json_decode((string) $product->getRawOriginal('fulfillment_summary'), true);

                if (! is_array($summary) || blank($summary['type'] ?? null)) {
                    continue;
                }

                for ($unitNumber = 1; $unitNumber <= $line->quantity; $unitNumber++) {
                    $idempotencyKey = sprintf(
                        'order:%s:line:%s:unit:%d',
                        $order->getKey(),
                        $line->getKey(),
                        $unitNumber,
                    );

                    FulfillmentUnit::query()->firstOrCreate(
                        ['idempotency_key' => $idempotencyKey],
                        [
                            'public_id' => (string) Str::ulid(),
                            'order_id' => $order->getKey(),
                            'order_line_id' => $line->getKey(),
                            'product_id' => $product->getKey(),
                            'quantity' => 1,
                            'type' => (string) ($summary['type'] ?? 'license'),
                            'provider' => (string) ($summary['provider'] ?? 'fake-keygen'),
                            'status' => 'pending',
                            'meta' => $summary,
                        ],
                    );
                }
            }

            return FulfillmentUnit::query()
                ->where('order_id', $order->getKey())
                ->oldest('id')
                ->get();
        });
    }
}
