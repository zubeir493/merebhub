@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-3xl px-5 py-16 lg:px-8">
        <div class="text-center">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-teal-50 text-teal-700">
                <x-heroicon-o-check-circle class="size-10" />
            </span>
            <h1 class="mt-5 text-4xl font-extrabold tracking-[-0.04em] text-zinc-950 sm:text-5xl">Thank you for your order!</h1>
            <p class="mt-3 text-base text-zinc-600">Your payment was confirmed and your software licenses are ready in your account.</p>
            <p class="mt-2 text-sm font-bold text-zinc-500">Order reference: <span class="font-mono text-zinc-800">{{ $order->reference }}</span></p>
        </div>

        @if ($order->productLines->isNotEmpty())
            <div class="mt-10 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-100 bg-zinc-50/70 px-6 py-4">
                    <h2 class="text-sm font-extrabold text-zinc-900">Purchased software</h2>
                </div>
                <div class="divide-y divide-zinc-100 px-6">
                    @foreach ($order->productLines as $line)
                        @php
                            $variant = $line->purchasable;
                            $product = $variant?->product;
                            $cover = $product instanceof \App\Models\Product
                                ? $product->coverUrl()
                                : ($product ? \App\Models\Product::query()->find($product->id)?->coverUrl() : null);
                        @endphp
                        <div class="flex items-center justify-between gap-4 py-4">
                            <div class="flex min-w-0 items-center gap-3.5">
                                @if ($cover)
                                    <img src="{{ $cover }}" alt="" class="size-12 shrink-0 rounded-lg bg-zinc-100 object-cover">
                                @else
                                    <div class="grid size-12 shrink-0 place-items-center rounded-lg bg-teal-50 text-teal-700">
                                        <x-heroicon-o-cube class="size-6" />
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-extrabold text-zinc-950">{{ $line->description }}</div>
                                    <div class="mt-0.5 text-xs text-zinc-500">Qty: {{ $line->quantity }} &middot; Digital license</div>
                                </div>
                            </div>
                            <div class="text-right text-sm font-extrabold text-zinc-950">
                                {{ $line->format('sub_total') }}
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="flex items-center justify-between border-t border-zinc-100 bg-zinc-50/50 px-6 py-4">
                    <span class="text-sm font-bold text-zinc-700">Total paid</span>
                    <strong class="text-base font-extrabold text-zinc-950">{{ $order->format('total') }}</strong>
                </div>
            </div>
        @endif

        <div class="mt-8 rounded-xl border border-teal-200 bg-teal-50/70 p-5">
            <div class="flex gap-3.5">
                <x-heroicon-o-key class="mt-0.5 size-5 shrink-0 text-teal-700" aria-hidden="true" />
                <div>
                    <h3 class="font-extrabold text-teal-950">Accessing your software &amp; license keys</h3>
                    <p class="mt-1 text-sm leading-6 text-teal-900">Your software licenses, offline activation files, and direct download links have been added to your account. You can copy your keys and download files immediately.</p>
                </div>
            </div>
        </div>

        <div class="mt-10 flex flex-col justify-center gap-3 sm:flex-row sm:items-center">
            <a href="{{ route('account.purchases') }}" class="btn-primary">
                <x-heroicon-o-key class="size-4" /> View licenses &amp; downloads
            </a>
            <a href="{{ route('account.orders') }}" class="btn-dark">
                <x-heroicon-o-document-text class="size-4" /> View order receipt
            </a>
            <a href="{{ route('store.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md border border-zinc-300 bg-white px-5 py-3 text-sm font-extrabold text-zinc-700 transition hover:border-zinc-400 hover:text-zinc-950">
                Continue shopping
            </a>
        </div>
    </div>
@endsection
