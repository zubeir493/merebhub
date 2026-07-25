@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-md px-5 py-16">
        <h1 class="text-3xl font-extrabold">Welcome back</h1>
        <p class="mt-2 text-sm text-zinc-600">Sign in to access your licenses and downloads.</p>
        <form method="POST" action="{{ route('login') }}" class="mt-8">
            @csrf
            <label class="form-label">Email</label>
            <input name="email" type="email" value="{{ old('email') }}" class="form-input" required autofocus>
            @error('email')<p class="form-error">{{ $message }}</p>@enderror
            <div class="mt-5 flex items-center justify-between"><label class="form-label mb-0">Password</label><a href="{{ route('password.request') }}" class="text-xs font-bold text-teal-700">Forgot password?</a></div>
            <input name="password" type="password" class="form-input mt-2" required>
            <label class="mt-4 flex items-center gap-2 text-sm font-semibold text-zinc-600"><input name="remember" type="checkbox" class="rounded border-zinc-300 text-teal-600"> Keep me signed in</label>
            <button class="btn-dark mt-6 w-full">Sign in</button>
        </form>
        <p class="mt-6 text-center text-sm text-zinc-600">New to MerebHub? <a href="{{ route('register') }}" class="font-extrabold text-teal-700">Create an account</a></p>
    </div>
@endsection
