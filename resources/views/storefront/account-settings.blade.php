@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-2xl px-5 py-12 lg:px-8">
        <p class="text-sm font-extrabold uppercase text-teal-700">Your account</p>
        <h1 class="mt-2 border-b border-zinc-200 pb-6 text-4xl font-extrabold">Account settings</h1>
        <form method="POST" action="{{ route('account.settings.update') }}" class="mt-8 grid gap-6">
            @csrf
            @method('PATCH')
            <div><label class="form-label">Name</label><input name="name" value="{{ old('name', $user->name) }}" class="form-input" required>@error('name')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div><label class="form-label">Email</label><input name="email" type="email" value="{{ old('email', $user->email) }}" class="form-input" required>@error('email')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div class="border-t border-zinc-200 pt-6">
                <h2 class="font-extrabold">Change password</h2>
                <p class="mt-1 text-sm text-zinc-500">Leave these fields empty to keep your current password.</p>
            </div>
            <div><label class="form-label">Current password</label><input name="current_password" type="password" class="form-input">@error('current_password')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label class="form-label">New password</label><input name="password" type="password" class="form-input">@error('password')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Confirm password</label><input name="password_confirmation" type="password" class="form-input"></div>
            </div>
            <div><button class="btn-dark">Save changes</button></div>
        </form>
    </div>
@endsection
