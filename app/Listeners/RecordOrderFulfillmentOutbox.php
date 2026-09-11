<?php

namespace App\Listeners;

use App\Domain\Shared\Actions\StoreOutboxMessageAction;
use App\Jobs\ProcessFulfillmentOutboxMessage;
use Lunar\Core\Events\Orders\OrderPlaced;

class RecordOrderFulfillmentOutbox
{
    public function __construct(private StoreOutboxMessageAction $outbox) {}

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        $message = $this->outbox->handle(
            'order.fulfillment.requested',
            ['order_id' => $order->getKey()],
            $order,
            dedupeKey: 'order.fulfillment.requested:'.$order->getKey(),
        );

        if ($message->wasRecentlyCreated) {
            ProcessFulfillmentOutboxMessage::dispatch($message->getKey())->afterCommit();
        }
    }
}
