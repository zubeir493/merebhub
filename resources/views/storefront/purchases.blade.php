@extends('storefront.account.layout')

@section('account-content')
    <header class="mb-8">
        <h1 class="text-4xl font-extrabold tracking-tight">Purchases</h1>
        <p class="mt-2 text-zinc-600">View and manage your purchased software and licenses.</p>
    </header>

    <div class="space-y-6">
        @forelse ($orders as $order)
            <article class="grid gap-6 rounded-2xl border border-zinc-200 bg-white p-5 sm:grid-cols-[112px_1fr_auto] sm:items-center shadow-sm">
                <img src="{{ $order->product->coverUrl() }}" alt="{{ $order->product->name }}" class="aspect-square w-28 rounded-lg object-cover">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-lg font-extrabold">{{ $order->product->name }}</h2>
                        <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs font-bold capitalize text-zinc-600">{{ $order->status->value }}</span>
                    </div>
                    <p class="mt-2 text-xs font-semibold text-zinc-500">Order {{ $order->public_id }} · {{ number_format($order->total_minor / 100, 2) }} {{ $order->currency }}</p>
                    @if ($order->license)
                        <div class="mt-4 rounded-lg bg-zinc-50 px-3 py-2 font-mono text-sm font-bold text-zinc-700">{{ $order->license->license_key }}</div>
                    @elseif ($order->fulfillment_error)
                        <p class="mt-3 text-sm font-semibold text-rose-600">License delivery needs admin attention.</p>
                    @else
                        <p class="mt-3 text-sm font-semibold text-amber-700">Your license is being prepared.</p>
                    @endif
                </div>
                @if ($downloadUrls[$order->id])
                    <a href="{{ $downloadUrls[$order->id] }}" class="btn-dark">
                        <x-heroicon-o-arrow-down-tray class="size-4" aria-hidden="true" /> Download
                    </a>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 py-16 text-center">
                <x-heroicon-o-shopping-bag class="mx-auto size-12 text-zinc-400" aria-hidden="true" />
                <h2 class="mt-4 text-lg font-semibold">Your library is empty</h2>
                <p class="mt-2 text-sm text-zinc-600">Start browsing and purchase some software.</p>
                <a href="{{ route('store.index') }}" class="mt-6 inline-block text-sm font-bold text-teal-700">Browse software</a>
            </div>
        @endforelse
    </div>
@endsection
