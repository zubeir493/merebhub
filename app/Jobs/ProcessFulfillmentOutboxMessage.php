<?php

namespace App\Jobs;

use App\Domain\Fulfillment\Actions\CreateFulfillmentUnitsAction;
use App\Domain\Fulfillment\Actions\ProvisionFulfillmentUnitAction;
use App\Models\OutboxMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lunar\Core\Models\Order;
use Throwable;

class ProcessFulfillmentOutboxMessage implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var array<int, int> */
    public array $backoff = [5, 30, 120];

    public function __construct(public readonly int $outboxMessageId) {}

    public function middleware(): array
    {
        return [new WithoutOverlapping('fulfillment-outbox:'.$this->outboxMessageId)];
    }

    public function handle(
        CreateFulfillmentUnitsAction $createUnits,
        ProvisionFulfillmentUnitAction $provisionUnit,
    ): void {
        $message = DB::transaction(function (): ?OutboxMessage {
            $message = OutboxMessage::query()->lockForUpdate()->find($this->outboxMessageId);

            if ($message === null || $message->published_at !== null) {
                return null;
            }

            $message->increment('attempts');

            return $message->fresh();
        });

        if ($message === null) {
            return;
        }

        try {
            $order = Order::query()->findOrFail((int) data_get($message->payload, 'order_id'));
            $units = $createUnits->handle($order);

            foreach ($units as $unit) {
                $provisionUnit->handle($unit);
            }

            $message->forceFill([
                'published_at' => now(),
                'last_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $message->forceFill([
                'last_error' => Str::limit($exception->getMessage(), 2000, ''),
                'available_at' => now()->addSeconds(30),
            ])->save();

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        OutboxMessage::query()
            ->whereKey($this->outboxMessageId)
            ->update([
                'last_error' => $exception === null ? 'Fulfillment job failed.' : Str::limit($exception->getMessage(), 2000, ''),
                'available_at' => now(),
            ]);
    }
}
