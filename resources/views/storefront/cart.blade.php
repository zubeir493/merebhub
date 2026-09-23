@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-6xl px-5 py-12 lg:px-8">
        <div class="flex items-end justify-between gap-6 border-b border-zinc-200 pb-6">
            <h1 class="text-5xl font-extrabold leading-none tracking-[-0.05em]">Your cart</h1>
            <span class="text-sm font-semibold text-zinc-500">{{ $items->sum('quantity') }} {{ Str::plural('item', $items->sum('quantity')) }}</span>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($items->isEmpty())
            <section class="py-20 text-center">
                <span class="mx-auto grid size-14 place-items-center rounded-full bg-zinc-100"><x-heroicon-o-shopping-cart class="size-7 text-zinc-500" /></span>
                <h2 class="mt-5 text-xl font-extrabold">Your cart is empty</h2>
                <p class="mt-2 text-sm text-zinc-500">Explore the catalog and add software when you are ready.</p>
                <a href="{{ route('store.index') }}" class="btn-primary mt-6">Browse software catalog</a>
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
                                <img src="{{ $product->coverUrl() }}" alt="{{ $product->name }}" class="aspect-square w-full rounded-sm bg-zinc-100 object-cover">
                            </a>
                            <div class="min-w-0">
                                <a href="{{ route('products.show', $product) }}" class="mt-1 block text-lg font-extrabold hover:text-teal-700">{{ $product->name }}</a>
                                <p class="text-xs font-bold text-zinc-500">{{ $product->author?->name ?? 'Independent' }}</p>
                                <p class="mt-2 text-sm font-semibold text-zinc-600 tabular-nums">{{ $item->unitPrice->format() }} · {{ $product->variantDisplayName($variant) }}</p>
                                <form method="POST" action="{{ route('cart.destroy', $item->id) }}" class="mt-3">
                                    @csrf
                                    @method('DELETE')
                                    <button class="flex min-h-11 items-center gap-1 text-xs font-bold text-rose-600 hover:text-rose-800" aria-label="Remove {{ $product->name }} from cart">
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
                    <div class="mt-5 flex items-center justify-between text-sm text-zinc-600">
                        <span>Subtotal</span>
                        <strong class="text-zinc-950">{{ $cart->subTotal->format() }}</strong>
                    </div>

                    @if ($cart->discountTotal && $cart->discountTotal->value > 0)
                        <div class="mt-3 flex items-center justify-between text-sm text-emerald-700">
                            <span class="flex items-center gap-1.5 font-bold">
                                <x-heroicon-s-tag class="size-4 text-emerald-600" />
                                <span>Discount @if($cart->coupon_code)({{ $cart->coupon_code }})@endif</span>
                            </span>
                            <strong class="font-extrabold">-{{ $cart->discountTotal->format() }}</strong>
                        </div>
                    @endif

                    <div class="mt-4 flex items-center justify-between border-b border-zinc-200 pb-5 text-sm text-zinc-600">
                        <span>Currency</span>
                        <span>ETB (Ethiopian Birr)</span>
                    </div>

                    {{-- Coupon Box --}}
                    @if ($cart->coupon_code && $cart->discountTotal && $cart->discountTotal->value > 0)
                        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50/70 p-3.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="grid size-7 place-items-center rounded-lg bg-emerald-600 text-white">
                                        <x-heroicon-s-ticket class="size-4" />
                                    </span>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs font-black uppercase tracking-wider text-emerald-950">{{ $cart->coupon_code }}</span>
                                            <span class="rounded bg-emerald-200/80 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800">APPLIED</span>
                                        </div>
                                        <p class="text-xs font-semibold text-emerald-700">-{{ $cart->discountTotal->format() }} off</p>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('cart.coupon.remove') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="flex min-h-8 items-center gap-1 rounded-md px-2 py-1 text-xs font-bold text-rose-600 transition hover:bg-rose-50 hover:text-rose-700" title="Remove coupon">
                                        <x-heroicon-o-trash class="size-3.5" />
                                        <span>Remove</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('cart.coupon.apply') }}" class="mt-5">
                            @csrf
                            <label for="coupon_code" class="block text-xs font-bold uppercase tracking-wider text-zinc-500">Promo Code</label>
                            <div class="mt-1.5 flex gap-2">
                                <input
                                    type="text"
                                    id="coupon_code"
                                    name="coupon_code"
                                    value="{{ old('coupon_code') }}"
                                    placeholder="e.g. MEREB20"
                                    class="block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold uppercase placeholder:normal-case placeholder:font-normal placeholder:text-zinc-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                    required
                                >
                                <button type="submit" class="btn-dark shrink-0 px-4 py-2 text-xs font-bold uppercase tracking-wider transition hover:bg-zinc-800">
                                    Apply
                                </button>
                            </div>
                            @error('coupon_code')
                                <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </form>
                    @endif

                    <div class="mt-5 flex items-end justify-between border-t border-zinc-200 pt-5">
                        <strong>Total</strong>
                        <strong class="text-xl font-black text-zinc-950">{{ $cart->total->format() }}</strong>
                    </div>
                    <button type="button" data-share-cart="{{ route('cart.share') }}" class="mt-5 flex min-h-11 w-full items-center justify-center gap-2 rounded-md border border-zinc-300 px-4 py-3 text-sm font-extrabold text-teal-900 transition hover:border-teal-400 hover:bg-teal-50"><x-heroicon-o-share class="size-4" /> Share cart</button>
                    <p class="mt-2 hidden text-center text-xs font-semibold text-emerald-700" data-share-cart-status role="status"></p>
                    <form method="POST" action="{{ route('checkout.store') }}" class="mt-6">
                        @csrf
                        <button type="submit" class="btn-primary w-full"><x-heroicon-o-lock-closed class="size-4" /> Checkout</button>
                    </form>
                    @auth
                        <form method="POST" action="{{ route('checkout.store') }}" class="mt-6">
                            @csrf
                            <button type="submit" class="btn-primary w-full"><x-heroicon-o-lock-closed class="size-4" /> Checkout</button>
                        </form>
                    @else
                        <a href="{{ route('login', ['intent' => 'checkout', 'redirect' => route('cart.index')]) }}" class="btn-primary mt-6 flex w-full items-center justify-center gap-2">
                            <x-heroicon-o-lock-closed class="size-4" /> Checkout
                        </a>
                    @endauth
                    <p class="mt-4 text-center text-xs leading-5 text-zinc-500">Instant digital delivery with secure payment processing.</p>
                </aside>
            </div>
        @endif
    </div>
@endsection
