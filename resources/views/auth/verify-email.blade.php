@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-lg px-5 py-16 text-center">
        <span class="mx-auto grid size-12 place-items-center rounded-lg bg-teal-50 text-teal-700"><x-heroicon-o-envelope class="size-6" /></span>
        <h1 class="mt-5 text-3xl font-extrabold">Verify your email</h1>
        <p class="mt-3 text-sm leading-6 text-zinc-600">We sent a verification link to {{ auth()->user()->email }}. Open it to finish setting up your account.</p>
        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">@csrf<button class="btn-primary">Send another link</button></form>

        @if ($localVerificationUrl)
            <div class="mt-8 rounded-xl border border-amber-200 bg-amber-50 p-4 text-left">
                <p class="text-sm font-semibold text-amber-900">Local development</p>
                <p class="mt-1 text-xs leading-5 text-amber-800">
                    Mail is set to <code class="rounded bg-amber-100 px-1">log</code>, so no real email is sent.
                    Use this plain link (do not copy from the HTML mail dump in the log — those use <code class="rounded bg-amber-100 px-1">&amp;</code> and will 403):
                </p>
                <a href="{{ $localVerificationUrl }}" class="mt-3 block break-all text-sm font-medium text-teal-800 underline underline-offset-2 hover:text-teal-950">
                    {{ $localVerificationUrl }}
                </a>
            </div>
        @endif
    </div>
@endsection
