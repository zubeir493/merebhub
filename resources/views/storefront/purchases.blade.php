@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-5xl px-5 py-12 lg:px-8">
        <p class="text-sm font-extrabold uppercase text-teal-700">Your library</p>
        <h1 class="mt-2 text-4xl font-extrabold">Purchases</h1>
        <p class="mt-3 text-zinc-600">Licenses and latest builds bought with {{ auth()->user()->email }}.</p>
        <div class="mt-8 grid gap-4">
            @forelse ($orders as $order)
                <article class="grid gap-5 rounded-lg border border-zinc-200 p-5 sm:grid-cols-[112px_1fr_auto] sm:items-center">
                    <img src="{{ $order->product->coverUrl() }}" alt="" class="aspect-square w-28 rounded-lg object-cover">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-lg font-extrabold">{{ $order->product->name }}</h2>
                            <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs font-bold capitalize text-zinc-600">{{ $order->status->value }}</span>
                        </div>
                        <p class="mt-2 text-xs font-semibold text-zinc-500">Order #{{ $order->wc_order_id }} · {{ number_format((float) $order->amount) }} {{ $order->currency }}</p>
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
                            <x-heroicon-o-arrow-down-tray class="size-4" /> Download
                        </a>
                    @endif
                </article>
            @empty
                <div class="border-y border-zinc-200 py-16 text-center">
                    <x-heroicon-o-shopping-bag class="mx-auto size-9 text-zinc-400" />
                    <h2 class="mt-4 text-lg font-extrabold">Your library is empty</h2>
                    <a href="{{ route('home') }}#catalog" class="mt-4 inline-block text-sm font-bold text-teal-700">Browse software</a>
                </div>
            @endforelse
        </div>
    </div>
@endsection
