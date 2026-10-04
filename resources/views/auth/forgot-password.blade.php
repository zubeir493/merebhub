@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-md px-5 py-16">
        <h1 class="text-3xl font-extrabold">Choose a new way back in</h1>
        <p class="mt-2 text-sm leading-6 text-zinc-600">Enter your email and we’ll send a secure link to reset your password.</p>
        <form method="POST" action="{{ route('password.email') }}" class="mt-8">
            @csrf
            <label class="form-label">Email</label><input name="email" type="email" value="{{ old('email') }}" class="form-input" required autofocus>@error('email')<p class="form-error">{{ $message }}</p>@enderror
            <x-honeypot />
            <button class="btn-primary mt-6 w-full">Email me a reset link</button>
        </form>
    </div>
@endsection
