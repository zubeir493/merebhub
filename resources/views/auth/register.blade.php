@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-md px-5 py-16">
        <h1 class="text-3xl font-extrabold tracking-[-0.035em] text-zinc-950">{{ $title ?? 'Create your MerebHub account' }}</h1>
        <p class="mt-2 text-sm text-zinc-600">{{ $subtitle ?? 'Keep purchases, licenses, downloads, and support in one place.' }}</p>
        @php
            $authParams = array_filter(array_merge(request()->query(), [
                'intent' => $intent ?? request('intent'),
                'redirect' => $redirect ?? request('redirect'),
            ]));
        @endphp
        <form method="POST" action="{{ route('register', $authParams) }}" class="mt-8">
            @csrf
            <label class="form-label">Name</label><input name="name" value="{{ old('name') }}" class="form-input" required>@error('name')<p class="form-error">{{ $message }}</p>@enderror
            <label class="form-label mt-5">Email</label><input name="email" type="email" value="{{ old('email') }}" class="form-input" required>@error('email')<p class="form-error">{{ $message }}</p>@enderror
            <label class="form-label mt-5">Password</label><input name="password" type="password" class="form-input" required>@error('password')<p class="form-error">{{ $message }}</p>@enderror
            <label class="form-label mt-5">Confirm your password</label><input name="password_confirmation" type="password" class="form-input" required>
            <x-honeypot />
            <label class="mt-5 flex items-start gap-3 text-sm leading-6 text-zinc-600">
                <input name="terms" type="checkbox" value="1" class="mt-1 rounded border-zinc-300 text-teal-600" required>
                <span>By creating an account, you agree to the <a href="{{ route('legal.terms') }}" class="font-bold text-teal-700 underline">Terms of Service</a> and <a href="{{ route('legal.privacy') }}" class="font-bold text-teal-700 underline">Privacy Policy</a>.</span>
            </label>
            @error('terms')<p class="form-error">{{ $message }}</p>@enderror
            <button class="btn-primary mt-6 w-full">Create account and continue</button>
        </form>
        <p class="mt-6 text-center text-sm text-zinc-600">Already registered? <a href="{{ route('login', $authParams) }}" class="font-extrabold text-teal-700">Sign in</a></p>
    </div>
@endsection
