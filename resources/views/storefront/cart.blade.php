@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-6xl px-5 py-12 lg:px-8">
        <div class="flex items-end justify-between gap-6 border-b border-zinc-200 pb-6">
            <h1 class="text-5xl font-extrabold leading-none tracking-[-0.05em]">Your cart</h1>
            <span data-cart-header-count class="text-sm font-semibold text-zinc-500">{{ $items->sum('quantity') }} {{ Str::plural('item', $items->sum('quantity')) }}</span>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        <section data-cart-empty class="{{ $items->isEmpty() ? '' : 'hidden' }} py-20 text-center">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-zinc-100"><x-heroicon-o-shopping-cart class="size-7 text-zinc-500" /></span>
            <h2 class="mt-5 text-xl font-extrabold">Your cart is empty</h2>
            <p class="mt-2 text-sm text-zinc-500">Explore the catalog and add software when you are ready.</p>
            <a href="{{ route('store.index') }}" class="btn-primary mt-6">Browse software catalog</a>
        </section>

        <div data-cart-content class="{{ $items->isEmpty() ? 'hidden' : '' }} mt-8 grid gap-10 lg:grid-cols-[1fr_360px]">
            <div data-cart-items class="divide-y divide-zinc-200">
                @foreach ($items as $item)
                    @php
                        $variant = $item->purchasable;
                        $product = $variant->product;
                    @endphp
                    <article data-cart-row="{{ $item->id }}" class="grid gap-5 py-6 sm:grid-cols-[112px_1fr_auto] sm:items-center">
                        <a href="{{ route('products.show', $product) }}">
                            <img src="{{ $product->coverUrl() }}" alt="{{ $product->name }}" class="aspect-square w-full rounded-sm bg-zinc-100 object-cover">
                        </a>
                        <div class="min-w-0">
                            <a href="{{ route('products.show', $product) }}" class="mt-1 block text-lg font-extrabold hover:text-teal-700">{{ $product->name }}</a>
                            <p class="text-xs font-bold text-zinc-500">{{ $product->author?->name ?? 'Independent' }}</p>
                            <p data-cart-line-price="{{ $item->id }}" class="mt-2 text-sm font-semibold text-zinc-600 tabular-nums">{{ $item->unitPrice->format() }} · {{ $product->variantDisplayName($variant) }}</p>
                            <button type="button" data-cart-remove="{{ $item->id }}" data-url="{{ route('cart.destroy', $item->id) }}" class="mt-3 flex min-h-11 items-center gap-1 text-xs font-bold text-rose-600 transition hover:text-rose-800" aria-label="Remove {{ $product->name }} from cart">
                                <x-heroicon-o-trash class="size-4" /> Remove
                            </button>
                        </div>
                        <div class="flex w-fit items-center gap-1 rounded-xl bg-zinc-50 p-1 ring-1 ring-zinc-950/10" aria-label="Quantity for {{ $product->name }}">
                            <button type="button" data-cart-qty-btn="dec" data-line-id="{{ $item->id }}" data-url="{{ route('cart.update', $item->id) }}" class="grid size-10 place-items-center rounded-lg bg-white text-zinc-700 shadow-sm transition hover:text-teal-700 disabled:bg-transparent disabled:text-zinc-300 disabled:shadow-none" aria-label="Decrease quantity" @disabled($item->quantity <= 1)>
                                <x-heroicon-o-minus class="size-4" />
                            </button>
                            <span data-cart-qty-display="{{ $item->id }}" class="min-w-9 text-center text-sm font-extrabold tabular-nums">{{ $item->quantity }}</span>
                            <button type="button" data-cart-qty-btn="inc" data-line-id="{{ $item->id }}" data-url="{{ route('cart.update', $item->id) }}" class="grid size-10 place-items-center rounded-lg bg-white text-zinc-700 shadow-sm transition hover:text-teal-700 disabled:bg-transparent disabled:text-zinc-300 disabled:shadow-none" aria-label="Increase quantity" @disabled($item->quantity >= 10)>
                                <x-heroicon-o-plus class="size-4" />
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>
            <aside class="h-fit lg:sticky lg:top-28">
                <h2 class="text-lg font-extrabold">Order summary</h2>
                <div class="mt-5 flex items-center justify-between text-sm text-zinc-600">
                    <span>Subtotal</span>
                    <strong data-cart-subtotal class="text-zinc-950">{{ $cart?->subTotal?->format() ?? '0.00 ETB' }}</strong>
                </div>

                <div data-cart-discount-row class="{{ $cart && $cart->discountTotal && $cart->discountTotal->value > 0 ? '' : 'hidden' }} mt-3 flex items-center justify-between text-sm">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-teal-200 bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-800">
                        <x-heroicon-s-tag class="size-3.5 text-teal-600" />
                        <span data-cart-coupon-code class="font-bold uppercase tracking-wider">{{ $cart?->coupon_code }}</span>
                        <button type="button" data-remove-coupon data-url="{{ route('cart.coupon.remove') }}" class="ml-1 text-teal-700 transition hover:text-rose-600" title="Remove coupon">
                            <x-heroicon-m-x-mark class="size-3.5" />
                        </button>
                    </span>
                    <strong data-cart-discount class="font-extrabold text-teal-700">-{{ $cart?->discountTotal?->format() }}</strong>
                </div>

                <div class="mt-4 flex items-center justify-between border-b border-zinc-200 pb-5 text-sm text-zinc-600">
                    <span>Currency</span>
                    <span>ETB (Ethiopian Birr)</span>
                </div>

                {{-- Clean Shopify/WooCommerce Style Coupon Form --}}
                <div class="mt-5">
                    <form data-cart-coupon-form action="{{ route('cart.coupon.apply') }}" method="POST" class="flex gap-2">
                        @csrf
                        <div class="relative flex-1">
                            <input
                                type="text"
                                name="coupon_code"
                                placeholder="Discount code"
                                class="block w-full rounded-lg border border-zinc-300 px-3.5 py-3 text-sm font-semibold uppercase placeholder:normal-case placeholder:font-normal placeholder:text-zinc-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                required
                                autocomplete="off"
                            >
                        </div>
                        <button type="submit" class="btn-dark shrink-0 px-4 py-2 text-xs font-bold uppercase tracking-wider transition hover:bg-zinc-800">
                            Apply
                        </button>
                    </form>
                    <p data-cart-coupon-error class="mt-2 hidden text-xs font-semibold text-rose-600" role="alert"></p>
                </div>

                <div class="mt-5 flex items-end justify-between border-t border-zinc-200 pt-5">
                    <strong>Total</strong>
                    <strong data-cart-total class="text-xl font-black text-zinc-950">{{ $cart?->total?->format() ?? '0.00 ETB' }}</strong>
                </div>

                <button type="button" data-share-cart="{{ route('cart.share') }}" class="mt-5 flex min-h-11 w-full items-center justify-center gap-2 rounded-md border border-zinc-300 px-4 py-3 text-sm font-extrabold text-teal-900 transition hover:border-teal-400 hover:bg-teal-50">
                    <x-heroicon-o-share class="size-4" /> Share cart
                </button>
                <p class="mt-2 hidden text-center text-xs font-semibold text-emerald-700" data-share-cart-status role="status"></p>

                @auth
                    <form method="POST" action="{{ route('checkout.store') }}" class="mt-4">
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
    </div>
@endsection
