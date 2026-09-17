<?php

namespace App\Domain\Fulfillment\Actions;

use App\Models\FulfillmentUnit;
use Illuminate\Support\Collection;
use LogicException;
use Lunar\Core\Models\Order;
use Throwable;

class RegenerateOrderLicensesAction
{
    public function __construct(
        private readonly CreateFulfillmentUnitsAction $createUnits,
        private readonly ProvisionFulfillmentUnitAction $provisionUnit,
    ) {}

    /**
     * @return array{attempted: int, succeeded: int, failed: array<int, array{unit_id: int, message: string}>}
     */
    public function handle(Order $order): array
    {
        if ($order->placed_at === null) {
            throw new LogicException('Only paid orders can regenerate licenses.');
        }

        /** @var Collection<int, FulfillmentUnit> $units */
        $units = $this->createUnits->handle($order);
        $missingUnits = $units
            ->filter(fn (FulfillmentUnit $unit): bool => ! $unit->entitlement()->exists())
            ->values();
        $failed = [];
        $succeeded = 0;

        foreach ($missingUnits as $unit) {
            try {
                $this->provisionUnit->handle($unit);
                $succeeded++;
            } catch (Throwable $exception) {
                report($exception);
                $failed[] = [
                    'unit_id' => $unit->getKey(),
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return [
            'attempted' => $missingUnits->count(),
            'succeeded' => $succeeded,
            'failed' => $failed,
        ];
    }
}
