@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-md px-5 py-16">
        <h1 class="text-3xl font-extrabold">Choose a new password</h1>
        <form method="POST" action="{{ route('password.update') }}" class="mt-8">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="form-label">Email</label><input name="email" type="email" value="{{ old('email', $email) }}" class="form-input" required>
            <label class="form-label mt-5">New password</label><input name="password" type="password" class="form-input" required>@error('password')<p class="form-error">{{ $message }}</p>@enderror
            <label class="form-label mt-5">Confirm password</label><input name="password_confirmation" type="password" class="form-input" required>
            <button class="btn-primary mt-6 w-full">Update password</button>
        </form>
    </div>
@endsection
