<?php

namespace App\Domain\Shared\Actions;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RecordAuditEventAction
{
    public function __construct(private Request $request) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        string $event,
        ?Model $actor = null,
        ?Model $subject = null,
        array $metadata = [],
    ): AuditEvent {
        $ipAddress = $this->request->ip();

        return AuditEvent::query()->create([
            'public_id' => (string) Str::uuid(),
            'event' => $event,
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $this->sanitize($metadata),
            'ip_hash' => $ipAddress === null ? null : hash_hmac('sha256', $ipAddress, (string) config('app.key')),
            'user_agent_summary' => Str::limit((string) $this->request->userAgent(), 255, ''),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitize(array $metadata): array
    {
        $sensitiveKeys = collect(config('marketplace.audit.sensitive_keys', []))
            ->map(fn (string $key): string => Str::lower($key))
            ->all();

        return collect($metadata)->mapWithKeys(function (mixed $value, string|int $key) use ($sensitiveKeys): array {
            $normalizedKey = Str::lower((string) $key);

            if (in_array($normalizedKey, $sensitiveKeys, true)) {
                return [(string) $key => '[REDACTED]'];
            }

            if (is_array($value)) {
                return [(string) $key => $this->sanitize($value)];
            }

            return [(string) $key => is_string($value) ? Str::limit($value, 1000, '') : $value];
        })->all();
    }
}
