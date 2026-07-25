@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-2xl px-5 py-16">
        @if ($order)
            <p class="text-sm font-extrabold uppercase text-teal-700">Order #{{ $order->wc_order_id }}</p>
            <h1 class="mt-2 text-3xl font-extrabold">Your purchase</h1>
            <article class="mt-7 rounded-lg border border-zinc-200 p-6">
                <div class="flex gap-4">
                    <img src="{{ $order->product->coverUrl() }}" alt="" class="size-24 rounded-lg object-cover">
                    <div><h2 class="text-xl font-extrabold">{{ $order->product->name }}</h2><p class="mt-2 text-sm font-bold capitalize text-zinc-500">{{ $order->status->value }} · {{ number_format((float) $order->amount) }} {{ $order->currency }}</p></div>
                </div>
                @if ($order->license)
                    <p class="mt-6 text-xs font-bold uppercase text-zinc-500">License key</p>
                    <div class="mt-2 rounded-lg bg-zinc-50 p-4 font-mono text-sm font-bold">{{ $order->license->license_key }}</div>
                @else
                    <p class="mt-6 rounded-lg bg-amber-50 p-4 text-sm font-bold text-amber-800">License delivery is still processing.</p>
                @endif
                @if ($downloadUrl)
                    <a href="{{ $downloadUrl }}" class="btn-dark mt-5"><x-heroicon-o-arrow-down-tray class="size-4" /> Download latest build</a>
                @endif
            </article>
        @else
            <div class="border-y border-zinc-200 py-14 text-center">
                <x-heroicon-o-exclamation-circle class="mx-auto size-9 text-zinc-400" />
                <h1 class="mt-4 text-2xl font-extrabold">No matching order</h1>
                <p class="mt-2 text-sm text-zinc-600">Check the email and reference, then try again.</p>
                <a href="{{ route('orders.lookup') }}" class="btn-primary mt-6">Try again</a>
            </div>
        @endif
    </div>
@endsection
