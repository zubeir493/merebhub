@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-md px-5 py-16">
        <h1 class="text-3xl font-extrabold">Create your account</h1>
        <p class="mt-2 text-sm text-zinc-600">Keep every purchase, license, and download in one place.</p>
        <form method="POST" action="{{ route('register') }}" class="mt-8">
            @csrf
            <label class="form-label">Name</label><input name="name" value="{{ old('name') }}" class="form-input" required>@error('name')<p class="form-error">{{ $message }}</p>@enderror
            <label class="form-label mt-5">Email</label><input name="email" type="email" value="{{ old('email') }}" class="form-input" required>@error('email')<p class="form-error">{{ $message }}</p>@enderror
            <label class="form-label mt-5">Password</label><input name="password" type="password" class="form-input" required>@error('password')<p class="form-error">{{ $message }}</p>@enderror
            <label class="form-label mt-5">Confirm password</label><input name="password_confirmation" type="password" class="form-input" required>
            <button class="btn-primary mt-6 w-full">Create account</button>
        </form>
        <p class="mt-6 text-center text-sm text-zinc-600">Already registered? <a href="{{ route('login') }}" class="font-extrabold text-teal-700">Sign in</a></p>
    </div>
@endsection
