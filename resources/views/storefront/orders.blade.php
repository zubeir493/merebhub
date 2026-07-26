@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-5xl px-5 py-12 lg:px-8">
        <p class="text-sm font-extrabold uppercase text-teal-700">Purchase history</p>
        <h1 class="mt-2 border-b border-zinc-200 pb-6 text-4xl font-extrabold">Previous orders</h1>
        <div class="mt-8 grid gap-5">
            @forelse ($orders as $order)
                <article class="rounded-lg border border-zinc-200">
                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-5 py-4">
                        <div>
                            <strong>Order #{{ $order->wc_order_id }}</strong>
                            <p class="mt-1 text-xs text-zinc-500">{{ $order->created_at->format('M j, Y') }} · {{ number_format((float) $order->amount) }} {{ $order->currency }}</p>
                        </div>
                        <span class="rounded-md bg-white px-2.5 py-1 text-xs font-bold capitalize text-zinc-700 ring-1 ring-zinc-200">{{ $order->status->value }}</span>
                    </header>
                    <div class="divide-y divide-zinc-100 px-5">
                        @php($displayItems = $order->items->isNotEmpty() ? $order->items : collect([(object) ['product' => $order->product, 'quantity' => 1, 'license' => $order->license]]))
                        @foreach ($displayItems as $item)
                            <div class="grid gap-4 py-5 sm:grid-cols-[72px_1fr]">
                                <img src="{{ $item->product?->coverUrl() }}" alt="" class="size-18 rounded-lg bg-zinc-100 object-cover">
                                <div>
                                    <div class="flex flex-wrap justify-between gap-2"><strong>{{ $item->product?->name }}</strong><span class="text-sm text-zinc-500">{{ $item->quantity }} {{ Str::plural('seat', $item->quantity) }}</span></div>
                                    @if ($item->license)
                                        <div class="mt-3 flex flex-wrap items-center gap-3 rounded-lg bg-teal-50 px-3 py-2">
                                            <x-heroicon-o-key class="size-4 text-teal-700" />
                                            <code class="break-all text-sm font-bold text-teal-950">{{ $item->license->license_key }}</code>
                                            <span class="ml-auto text-xs font-bold text-teal-800">{{ $item->license->activation_limit }} activations</span>
                                        </div>
                                    @elseif ($order->status->value === 'paid')
                                        <p class="mt-3 text-sm font-semibold text-amber-700">License delivery is being finalized.</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="py-20 text-center"><x-heroicon-o-receipt-percent class="mx-auto size-9 text-zinc-400" /><h2 class="mt-4 text-lg font-extrabold">No previous orders</h2><a href="{{ route('home') }}#catalog" class="mt-4 inline-block text-sm font-bold text-teal-700">Browse software</a></div>
            @endforelse
        </div>
    </div>
@endsection
