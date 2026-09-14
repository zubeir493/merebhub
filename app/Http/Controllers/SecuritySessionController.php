<?php

namespace App\Http\Controllers;

use App\Domain\Identity\Actions\RevokeUserSessionAction;
use App\Models\UserSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SecuritySessionController extends Controller
{
    public function index(Request $request): View
    {
        Gate::forUser($request->user())->authorize('viewAny', UserSession::class);

        $cutoff = now()->subMinutes(max(1, (int) config('session.lifetime', 120)));
        $sessions = $request->user()->userSessions()
            ->whereNull('revoked_at')
            ->where('last_active_at', '>=', $cutoff)
            ->latest('last_active_at')
            ->get();

        return view('storefront.account.security', [
            'sessions' => $sessions,
            'currentSessionId' => $request->session()->getId(),
        ]);
    }

    public function revoke(
        Request $request,
        string $session,
        RevokeUserSessionAction $revokeSession,
    ): RedirectResponse {
        $userSession = $request->user()->userSessions()
            ->where('public_id', $session)
            ->firstOrFail();

        Gate::forUser($request->user())->authorize('delete', $userSession);
        $revokeSession->handle($request->user(), $userSession, $request->session()->getId());

        return back()->with('status', 'The selected session has been signed out.');
    }
}
