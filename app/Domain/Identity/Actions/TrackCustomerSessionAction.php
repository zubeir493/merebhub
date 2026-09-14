<?php

namespace App\Domain\Identity\Actions;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Support\Str;

class TrackCustomerSessionAction
{
    public function handle(User $user, string $sessionId, ?string $userAgent, ?string $ipAddress): UserSession
    {
        $sessionHash = hash('sha256', $sessionId);
        $session = UserSession::query()->firstOrCreate(
            ['session_hash' => $sessionHash],
            [
                'user_id' => $user->getKey(),
                'session_id' => $sessionId,
                'device_label' => $this->deviceLabel($userAgent),
                'ip_hash' => $this->ipHash($ipAddress),
                'last_active_at' => now(),
            ],
        );

        if ($session->revoked_at !== null || (int) $session->user_id !== (int) $user->getKey()) {
            return $session;
        }

        if ($session->last_active_at === null || $session->last_active_at->lt(now()->subMinutes(5))) {
            $session->forceFill([
                'session_id' => $sessionId,
                'device_label' => $this->deviceLabel($userAgent),
                'ip_hash' => $this->ipHash($ipAddress),
                'last_active_at' => now(),
            ])->save();
        }

        return $session;
    }

    private function deviceLabel(?string $userAgent): string
    {
        $userAgent = Str::lower((string) $userAgent);
        $device = Str::contains($userAgent, ['mobile', 'android', 'iphone', 'ipad']) ? 'Mobile' : 'Desktop';
        $browser = match (true) {
            Str::contains($userAgent, ['edg/', 'edge/']) => 'Edge',
            Str::contains($userAgent, 'firefox/') => 'Firefox',
            Str::contains($userAgent, 'chrome/') => 'Chrome',
            Str::contains($userAgent, 'safari/') => 'Safari',
            default => 'Browser',
        };

        return $device.' · '.$browser;
    }

    private function ipHash(?string $ipAddress): ?string
    {
        return $ipAddress === null
            ? null
            : hash_hmac('sha256', $ipAddress, (string) config('app.key'));
    }
}
