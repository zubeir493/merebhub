<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function notice(): View
    {
        $user = auth()->user();
        $localVerificationUrl = null;

        if ($user && ! $user->hasVerifiedEmail() && $user->shouldLogEmailVerificationLink()) {
            $localVerificationUrl = $user->emailVerificationUrl();
        }

        return view('auth.verify-email', [
            'localVerificationUrl' => $localVerificationUrl,
        ]);
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $redirect = $this->safeRedirect($request->query('redirect'));
        $request->fulfill();

        return ($redirect ? redirect()->to($redirect) : redirect()->route('account.purchases'))
            ->with('status', 'Email verified.');
    }

    private function safeRedirect(mixed $redirect): ?string
    {
        if (! is_string($redirect) || blank($redirect)) {
            return null;
        }

        if (Str::startsWith($redirect, '/') && ! Str::startsWith($redirect, '//')) {
            return $redirect;
        }

        $redirectHost = parse_url($redirect, PHP_URL_HOST);
        $applicationHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return is_string($redirectHost) && is_string($applicationHost) && $redirectHost === $applicationHost
            ? $redirect
            : null;
    }

    public function send(Request $request): RedirectResponse
    {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Verification link sent.');
    }
}
