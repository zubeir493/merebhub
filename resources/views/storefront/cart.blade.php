@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-6xl px-5 py-12 lg:px-8">
        <div class="flex items-end justify-between gap-6 border-b border-zinc-200 pb-6">
            <h1 class="mt-2 text-4xl font-extrabold">Your cart</h1>
            <span class="text-sm font-semibold text-zinc-500">{{ $items->sum('quantity') }} {{ Str::plural('item', $items->sum('quantity')) }}</span>
        </div>

        @if ($items->isEmpty())
            <section class="py-20 text-center">
                <span class="mx-auto grid size-14 place-items-center rounded-full bg-zinc-100"><x-heroicon-o-shopping-cart class="size-7 text-zinc-500" /></span>
                <h2 class="mt-5 text-xl font-extrabold">Your cart is empty</h2>
                <p class="mt-2 text-sm text-zinc-500">Explore the catalog and add software when you are ready.</p>
                <a href="{{ route('store.index') }}" class="btn-primary mt-6">Explore Deals</a>
            </section>
        @else
            <div class="mt-8 grid gap-10 lg:grid-cols-[1fr_360px]">
                <div class="divide-y divide-zinc-200">
                    @foreach ($items as $item)
                        @php
                            $variant = $item->purchasable;
                            $product = $variant->product;
                        @endphp
                        <article class="grid gap-5 py-6 sm:grid-cols-[112px_1fr_auto] sm:items-center">
                            <a href="{{ route('products.show', $product) }}">
                                <img src="{{ $product->coverUrl() }}" alt="{{ $product->name }}" class="aspect-square w-full rounded-lg bg-zinc-100 object-cover">
                            </a>
                            <div class="min-w-0">
                                <a href="{{ route('products.show', $product) }}" class="mt-1 block text-lg font-extrabold hover:text-teal-700">{{ $product->name }}</a>
                                <p class="text-xs font-bold text-zinc-500">{{ $product->author?->name ?? 'Independent' }}</p>
                                <p class="mt-2 text-sm font-semibold text-zinc-600 tabular-nums">{{ $item->unitPrice->format() }} · {{ $variant->getOption() ?: 'Standard license' }}</p>
                                <form method="POST" action="{{ route('cart.destroy', $item->id) }}" class="mt-3">
                                    @csrf
                                    @method('DELETE')
                                    <button class="flex items-center text-xs font-bold text-rose-600 hover:text-rose-800" aria-label="Remove {{ $product->name }} from cart">
                                        <x-heroicon-o-trash class="size-4" /> Remove
                                    </button>
                                </form>
                            </div>
                            <div class="flex w-fit items-center gap-1 rounded-xl bg-zinc-50 p-1 ring-1 ring-zinc-950/10" aria-label="Quantity for {{ $product->name }}">
                                <form method="POST" action="{{ route('cart.update', $item->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="quantity" value="{{ max(1, $item->quantity - 1) }}">
                                    <button class="grid size-10 place-items-center rounded-lg bg-white text-zinc-700 shadow-sm hover:text-teal-700 disabled:bg-transparent disabled:text-zinc-300 disabled:shadow-none" aria-label="Decrease quantity" @disabled($item->quantity <= 1)><x-heroicon-o-minus class="size-4" /></button>
                                </form>
                                <span class="min-w-9 text-center text-sm font-extrabold tabular-nums">{{ $item->quantity }}</span>
                                <form method="POST" action="{{ route('cart.update', $item->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="quantity" value="{{ min(10, $item->quantity + 1) }}">
                                    <button class="grid size-10 place-items-center rounded-lg bg-white text-zinc-700 shadow-sm hover:text-teal-700 disabled:bg-transparent disabled:text-zinc-300 disabled:shadow-none" aria-label="Increase quantity" @disabled($item->quantity >= 10)><x-heroicon-o-plus class="size-4" /></button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
                <aside class="h-fit lg:sticky lg:top-28">
                    <h2 class="text-lg font-extrabold">Order summary</h2>
                    <div class="mt-5 flex items-center justify-between text-sm text-zinc-600"><span>Subtotal</span><strong class="text-zinc-950">{{ $cart->subTotal->format() }}</strong></div>
                    <div class="mt-4 flex items-center justify-between border-b border-zinc-200 pb-5 text-sm text-zinc-600"><span>Commerce engine</span><span>Lunar</span></div>
                    <div class="mt-5 flex items-end justify-between"><strong>Total</strong><strong class="text-md">{{ $cart->total->format() }}</strong></div>
                    <a href="{{ route('checkout.show') }}" class="btn-primary mt-6 w-full"><x-heroicon-o-lock-closed class="size-4" /> Continue to checkout</a>
                    <p class="mt-4 text-center text-xs leading-5 text-zinc-500">Taxes and totals are calculated by Lunar.</p>
                </aside>
            </div>
        @endif
    </div>
@endsection
