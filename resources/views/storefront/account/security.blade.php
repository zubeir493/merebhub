@extends('storefront.account.layout')

@section('account-content')
    <header class="mb-8">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-teal-700">Account protection</p>
        <h1 class="mt-2 text-4xl font-extrabold tracking-tight">Security &amp; sessions</h1>
        <p class="mt-2 max-w-2xl text-zinc-600">Review recent signed-in devices. Signing out a device removes its Laravel session from the configured session store.</p>
    </header>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        @forelse ($sessions as $session)
            @php($isCurrent = $session->isCurrent($currentSessionId))
            <article class="flex flex-col gap-4 border-b border-zinc-100 px-5 py-5 last:border-0 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex items-start gap-4">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl {{ $isCurrent ? 'bg-teal-50 text-teal-700' : 'bg-zinc-100 text-zinc-600' }}">
                        <x-heroicon-o-computer-desktop class="size-5" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 class="font-bold">{{ $session->device_label }}</h2>
                        <p class="mt-1 text-sm text-zinc-600">Last active {{ $session->last_active_at->diffForHumans() }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ $session->last_active_at->format('M j, Y · g:i A') }}{{ $isCurrent ? ' · This device' : '' }}</p>
                    </div>
                </div>
                @if ($isCurrent)
                    <span class="inline-flex w-fit rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-800 ring-1 ring-teal-200">Current session</span>
                @else
                    <form method="POST" action="{{ route('account.security.sessions.revoke', $session->public_id) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-rose-200 px-4 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50">Sign out device</button>
                    </form>
                @endif
            </article>
        @empty
            <div class="px-6 py-14 text-center">
                <x-heroicon-o-shield-check class="mx-auto size-12 text-zinc-400" aria-hidden="true" />
                <h2 class="mt-4 text-lg font-bold">No active sessions recorded</h2>
                <p class="mt-2 text-sm text-zinc-600">Your current device will appear here on your next account request.</p>
            </div>
        @endforelse
    </section>
@endsection
