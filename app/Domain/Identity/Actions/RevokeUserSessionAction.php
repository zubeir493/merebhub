<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Shared\Actions\RecordAuditEventAction;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class RevokeUserSessionAction
{
    public function __construct(
        private readonly Store $sessionStore,
        private readonly RecordAuditEventAction $audit,
    ) {}

    public function handle(User $user, UserSession $userSession, string $currentSessionId): void
    {
        $sessionId = DB::transaction(function () use ($user, $userSession, $currentSessionId): string {
            $session = UserSession::query()
                ->whereBelongsTo($user)
                ->whereKey($userSession->getKey())
                ->lockForUpdate()
                ->first();

            if (! $session) {
                throw (new ModelNotFoundException)->setModel(UserSession::class, [$userSession->getKey()]);
            }

            if ($session->isCurrent($currentSessionId)) {
                throw ValidationException::withMessages([
                    'session' => 'Use Log out to end the session you are currently using.',
                ]);
            }

            if ($session->revoked_at !== null) {
                return (string) $session->session_id;
            }

            $sessionId = (string) $session->session_id;
            $session->forceFill(['revoked_at' => now()])->save();
            $this->audit->handle(
                'security.session.revoked',
                actor: $user,
                subject: $session,
                metadata: ['device' => $session->device_label],
            );

            return $sessionId;
        });

        try {
            $this->sessionStore->getHandler()->destroy($sessionId);
        } catch (Throwable $exception) {
            Log::warning('A revoked customer session could not be removed from its session store.', [
                'exception' => $exception::class,
            ]);
        }
    }
}
