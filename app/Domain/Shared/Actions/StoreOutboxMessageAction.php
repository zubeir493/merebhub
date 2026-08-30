<?php

namespace App\Domain\Shared\Actions;

use App\Domain\Shared\Events\OutboxMessageRecorded;
use App\Models\OutboxMessage;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreOutboxMessageAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(
        string $event,
        array $payload,
        ?Model $aggregate = null,
        ?CarbonInterface $availableAt = null,
    ): OutboxMessage {
        $message = OutboxMessage::query()->create([
            'public_id' => (string) Str::uuid(),
            'event' => $event,
            'aggregate_type' => $aggregate?->getMorphClass(),
            'aggregate_id' => $aggregate === null ? null : (string) $aggregate->getKey(),
            'payload' => $payload,
            'available_at' => $availableAt,
        ]);

        event(new OutboxMessageRecorded($message));

        return $message;
    }
}
