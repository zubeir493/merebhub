@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-6xl px-5 py-12 lg:px-8">
        <div class="flex items-end justify-between gap-6 border-b border-zinc-200 pb-6">
            <div>
                <p class="text-sm font-extrabold uppercase text-teal-700">Ready when you are</p>
                <h1 class="mt-2 text-4xl font-extrabold">Your cart</h1>
            </div>
            @auth
                <span class="text-sm font-semibold text-zinc-500">{{ $items->count() }} {{ Str::plural('item', $items->count()) }}</span>
            @endauth
        </div>

        @guest
            <section class="py-20 text-center">
                <span class="mx-auto grid size-14 place-items-center rounded-full bg-zinc-100"><x-heroicon-o-shopping-cart class="size-7 text-zinc-500" /></span>
                <h2 class="mt-5 text-xl font-extrabold">Log in to use your cart</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-zinc-500">Your cart is linked to your MerebHub account so it stays available across devices.</p>
                <a href="{{ route('login') }}" class="btn-dark mt-6">Log in</a>
            </section>
        @else
            @if ($items->isEmpty())
                <section class="py-20 text-center">
                    <span class="mx-auto grid size-14 place-items-center rounded-full bg-zinc-100"><x-heroicon-o-shopping-cart class="size-7 text-zinc-500" /></span>
                    <h2 class="mt-5 text-xl font-extrabold">Your cart is empty</h2>
                    <p class="mt-2 text-sm text-zinc-500">Explore the catalog and add software when you are ready.</p>
                    <a href="{{ route('home') }}#catalog" class="btn-primary mt-6">Browse software</a>
                </section>
            @else
                <div class="mt-8 grid gap-10 lg:grid-cols-[1fr_360px]">
                    <div class="divide-y divide-zinc-200">
                        @foreach ($items as $item)
                            <article class="grid gap-5 py-6 sm:grid-cols-[112px_1fr_auto] sm:items-center">
                                <a href="{{ route('products.show', $item->product) }}">
                                    <img src="{{ $item->product->coverUrl() }}" alt="{{ $item->product->name }}" class="aspect-square w-28 rounded-lg bg-zinc-100 object-cover">
                                </a>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-zinc-500">{{ $item->product->author->name }}</p>
                                    <a href="{{ route('products.show', $item->product) }}" class="mt-1 block text-lg font-extrabold hover:text-teal-700">{{ $item->product->name }}</a>
                                    <p class="mt-1 text-sm text-zinc-500">{{ $item->productPlan?->name ?? 'Legacy plan' }}</p>
                                    <p class="mt-2 text-sm font-semibold text-zinc-600">{{ number_format(($item->productPlan?->price_minor ?? 0) / 100, 2) }} ETB</p>
                                    <form method="POST" action="{{ route('cart.destroy', $item) }}" class="mt-3">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs font-bold text-rose-600 hover:text-rose-800">Remove</button>
                                    </form>
                                </div>
                                <span class="text-sm font-bold text-zinc-500">Qty 1</span>
                            </article>
                        @endforeach
                    </div>
                    <aside class="h-fit border-t border-zinc-950 pt-5 lg:sticky lg:top-28">
                        <h2 class="text-lg font-extrabold">Order summary</h2>
                        <div class="mt-5 flex items-center justify-between text-sm text-zinc-600"><span>Subtotal</span><strong class="text-zinc-950">{{ number_format($subtotalMinor / 100, 2) }} ETB</strong></div>
                        <div class="mt-4 flex items-center justify-between border-b border-zinc-200 pb-5 text-sm text-zinc-600"><span>Payment</span><span>Secure Chapa checkout</span></div>
                        <div class="mt-5 flex items-end justify-between"><strong>Total</strong><strong class="text-2xl">{{ number_format($subtotalMinor / 100, 2) }} ETB</strong></div>
                        <form method="POST" action="{{ route('cart.checkout') }}" class="mt-6">
                            @csrf
                            <button class="btn-primary w-full"><x-heroicon-o-lock-closed class="size-4" /> Continue to Chapa</button>
                        </form>
                        <p class="mt-4 text-center text-xs leading-5 text-zinc-500">Prices and availability are revalidated by MerebHub before Chapa checkout.</p>
                    </aside>
                </div>
            @endif
        @endguest
    </div>
@endsection
