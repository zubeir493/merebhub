@extends('storefront.account.layout')

@section('account-content')
    <header class="mb-8">
        <h1 class="text-4xl font-extrabold tracking-tight">Previous Orders</h1>
        <p class="mt-2 text-zinc-600">View and manage your completed orders.</p>
    </header>

    <div class="space-y-6">
        @forelse ($orders as $order)
            <article class="bg-white rounded-2xl border border-zinc-200 p-6 shadow-sm">
                <header class="flex flex-wrap items-start justify-between gap-4 mb-6">
                    <div>
                        <strong class="text-lg font-semibold">Order #{{ $order->public_id }}</strong>
                        <p class="mt-1 text-sm text-zinc-500">{{ $order->created_at->format('M j, Y') }} · {{ number_format((float) $order->amount) }} {{ $order->currency }}</p>
                    </div>
                    <span class="rounded-md bg-white px-2.5 py-1 text-xs font-semibold capitalize text-zinc-700 ring-1 ring-zinc-200 border">{{ $order->status->value }}</span>
                </header>

                <div class="divide-y divide-zinc-100">
                    @php($displayItems = $order->items->isNotEmpty() ? $order->items : collect([(object) ['product' => $order->product, 'quantity' => 1, 'license' => $order->license]]))
                    @foreach ($displayItems as $item)
                        <div class="grid gap-5 py-6 first:pt-0 last:pb-0 sm:grid-cols-[72px_1fr]">
                            <img src="{{ $item->product?->coverUrl() }}" alt="{{ $item->product?->name }}" class="size-18 rounded-lg bg-zinc-100 object-cover">
                            <div>
                                <div class="flex flex-wrap justify-between gap-2">
                                    <strong class="{{ $item->license ? 'text-teal-700' : 'text-zinc-900' }}">{{ $item->product?->name }}</strong>
                                    <span class="text-sm text-zinc-500">{{ $item->quantity }} {{ Str::plural('seat', $item->quantity) }}</span>
                                </div>
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
            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 py-16 text-center">
                <x-heroicon-o-receipt-percent class="mx-auto size-12 text-zinc-400" />
                <h2 class="mt-4 text-lg font-semibold">No previous orders</h2>
                <p class="mt-2 text-sm text-zinc-600">Start browsing and purchase some software.</p>
            </div>
        @endforelse
    </div>
@endsection