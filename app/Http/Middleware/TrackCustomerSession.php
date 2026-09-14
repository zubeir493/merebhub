<?php

namespace App\Http\Middleware;

use App\Domain\Identity\Actions\TrackCustomerSessionAction;
use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackCustomerSession
{
    public function __construct(private readonly TrackCustomerSessionAction $trackSession) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $request->hasSession() || ! $user instanceof User) {
            return $next($request);
        }

        $session = $this->trackSession->handle(
            $user,
            $request->session()->getId(),
            $request->userAgent(),
            $request->ip(),
        );

        if ($session->revoked_at !== null || (int) $session->user_id !== (int) $user->getKey()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return new RedirectResponse(route('login'));
        }

        return $next($request);
    }
}
