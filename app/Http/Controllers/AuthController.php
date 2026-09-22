<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Lunar\Core\Models\Customer;

class AuthController extends Controller
{
    public function loginForm(Request $request): View
    {
        $content = $this->resolveAuthContent($request, isRegister: false);

        return view('auth.login', [
            'title' => $content['title'],
            'subtitle' => $content['subtitle'],
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Those credentials do not match our records.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account.orders'));
    }

    public function registerForm(Request $request): View
    {
        $content = $this->resolveAuthContent($request, isRegister: true);

        return view('auth.register', [
            'title' => $content['title'],
            'subtitle' => $content['subtitle'],
        ]);
    }

    /**
     * @return array{title: string, subtitle: string}
     */
    private function resolveAuthContent(Request $request, bool $isRegister = false): array
    {
        $intent = Str::lower((string) (
            $request->query('intent')
            ?: $request->query('from')
            ?: $request->query('redirect')
            ?: $request->session()->get('url.intended')
            ?: url()->previous()
        ));

        if (Str::contains($intent, ['checkout', 'cart', 'order'])) {
            return [
                'title' => $isRegister ? 'Create an account to complete your purchase' : 'Sign in to complete your purchase',
                'subtitle' => $isRegister
                    ? 'Create your account in seconds to finalize your checkout and receive your licenses.'
                    : 'Sign in to proceed with checkout and access your purchased software licenses.',
            ];
        }

        if (Str::contains($intent, 'wishlist')) {
            return [
                'title' => $isRegister ? 'Create an account to save to your wishlist' : 'Sign in to save to your wishlist',
                'subtitle' => $isRegister
                    ? 'Keep track of the software, tools, and developer libraries you want to revisit.'
                    : 'Sign in to bookmark software and keep track of products you want to buy later.',
            ];
        }

        if (Str::contains($intent, ['download', 'purchase', 'credential', 'license'])) {
            return [
                'title' => $isRegister ? 'Create an account to access your downloads' : 'Sign in to access your downloads',
                'subtitle' => $isRegister
                    ? 'Keep every purchase, license key, and software download in one place.'
                    : 'Access your product license keys, offline activation files, and software downloads.',
            ];
        }

        if (Str::contains($intent, ['review'])) {
            return [
                'title' => $isRegister ? 'Create an account to write a review' : 'Sign in to write a review',
                'subtitle' => $isRegister
                    ? 'Join MerebHub to rate and review verified Ethiopian software.'
                    : 'Share your feedback and experience with the developer and community.',
            ];
        }

        return [
            'title' => $isRegister ? 'Create your account' : 'Welcome back',
            'subtitle' => $isRegister
                ? 'Keep every purchase, license, and download in one place.'
                : 'Sign in to access your licenses and downloads.',
        ];
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create($request->safe()->only(['name', 'email', 'password']));
        $name = Str::of($user->name)->squish();
        $customer = Customer::create([
            'first_name' => $name->beforeLast(' ')->toString() ?: $name->toString(),
            'last_name' => $name->contains(' ') ? $name->afterLast(' ')->toString() : '',
        ]);
        $customer->users()->attach($user);
        $user->sendEmailVerificationNotification();
        Auth::login($user);

        return redirect()->route('verification.notice');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function forgotPasswordForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($validated);

        return back()->with('status', 'If that email exists, a reset link has been sent.');
    }

    public function resetPasswordForm(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->string('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset($validated, function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
