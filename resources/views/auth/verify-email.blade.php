@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-lg px-5 py-16 text-center">
        <span class="mx-auto grid size-12 place-items-center rounded-lg bg-teal-50 text-teal-700"><x-heroicon-o-envelope class="size-6" /></span>
        <h1 class="mt-5 text-3xl font-extrabold">Verify your email</h1>
        <p class="mt-3 text-sm leading-6 text-zinc-600">We sent a verification link to {{ auth()->user()->email }}. Open it to finish setting up your account.</p>
        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">@csrf<button class="btn-primary">Send another link</button></form>
    </div>
@endsection
