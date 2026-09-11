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
        ?string $dedupeKey = null,
    ): OutboxMessage {
        $attributes = [
            'event' => $event,
            'aggregate_type' => $aggregate?->getMorphClass(),
            'aggregate_id' => $aggregate === null ? null : (string) $aggregate->getKey(),
            'dedupe_key' => $dedupeKey,
        ];
        $values = [
            'public_id' => (string) Str::uuid(),
            'payload' => $payload,
            'available_at' => $availableAt,
        ];
        $message = $dedupeKey === null
            ? OutboxMessage::query()->create([...$attributes, ...$values])
            : OutboxMessage::query()->firstOrCreate($attributes, $values);

        if ($message->wasRecentlyCreated) {
            event(new OutboxMessageRecorded($message));
        }

        return $message;
    }
}
