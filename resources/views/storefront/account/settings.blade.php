@extends('storefront.account.layout')

@section('account-content')
    <header class="account-page-header">
        <h1 class="account-page-title">Account settings</h1>
        <p class="account-page-description">Manage your profile, email, and password.</p>
    </header>

    <form method="POST" action="{{ route('account.settings.update') }}" class="account-form-surface">
        @csrf
        @method('PATCH')

        <div class="space-y-6">
            <section>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="form-label">Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="form-input" required autocomplete="name">
                        @error('name')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="form-input" required autocomplete="email">
                        @error('email')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="border-t border-zinc-200 pt-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-zinc-900">Password</h2>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-input" autocomplete="current-password">
                        @error('current_password')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" id="password" name="password" class="form-input" autocomplete="new-password">
                        @error('password')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" autocomplete="new-password">
                    </div>
                </div>
            </section>

            <div>
                <button type="submit" class="btn-dark">
                    <x-heroicon-o-check-circle class="size-4" aria-hidden="true" />
                    Save Changes
                </button>
            </div>
        </div>
    </form>

    @if (session('status'))
        <div class="mt-4 rounded-lg bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800" x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-transition>
            {{ session('status') }}
        </div>
    @endif
@endsection
