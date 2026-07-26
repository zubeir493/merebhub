@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-[1400px] px-5 py-10 lg:px-8">
        <nav class="mb-7 flex items-center gap-2 text-xs font-semibold text-zinc-500">
            <a href="{{ route('home') }}" class="hover:text-teal-700">Discover</a>
            <x-heroicon-o-chevron-right class="size-3.5" />
            <span>{{ $product->category }}</span>
        </nav>
        <section class="grid gap-9 lg:grid-cols-[minmax(0,1.25fr)_minmax(340px,.75fr)]">
            <div class="overflow-hidden rounded-lg bg-zinc-100">
                <img src="{{ $product->coverUrl() }}" alt="{{ $product->name }}" class="aspect-[16/10] h-full w-full object-cover">
            </div>
            <div class="flex flex-col justify-center">
                <div class="flex items-center gap-2 text-sm font-bold text-zinc-500">
                    <span>{{ $product->category }}</span><span>·</span><span>{{ $product->platforms->pluck('name')->join(', ') }}</span>
                </div>
                <h1 class="mt-3 text-4xl font-extrabold leading-tight text-zinc-950">{{ $product->name }}</h1>
                <p class="mt-3 text-lg font-semibold leading-7 text-zinc-600">{{ $product->tagline }}</p>
                @if ($product->author->status === \App\Enums\AuthorStatus::Active && $product->author->is_public)
                    <a href="{{ route('vendors.show', $product->author) }}" class="mt-5 inline-flex items-center gap-2 text-sm font-extrabold text-teal-700">
                        By {{ $product->author->name }}
                        @if ($product->author->is_verified)<x-heroicon-o-check-badge class="size-4" />@endif
                    </a>
                @else
                    <p class="mt-5 text-sm font-extrabold text-zinc-600">By {{ $product->author->name }}</p>
                @endif
                <div class="mt-5 flex items-center gap-2 text-sm">
                    <x-heroicon-s-star class="size-5 text-amber-400" />
                    <strong>{{ number_format((float) $product->rating, 1) }}</strong>
                    <span class="text-zinc-500">{{ number_format($product->ratings_count) }} ratings</span>
                </div>
                <div class="mt-8 border-y border-zinc-200 py-6">
                    <div class="mt-5">
                        @if ($product->activePlans->isNotEmpty())
                        <form method="POST" action="{{ route('cart.store', $product) }}" class="flex-1">
                            @csrf
                            <label class="form-label" for="product-plan">Choose a plan</label>
                            <select id="product-plan" name="product_plan_id" class="form-input">
                                @foreach ($product->activePlans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }} — {{ number_format($plan->price_minor / 100, 2) }} ETB</option>
                                @endforeach
                            </select>
                            <button class="btn-primary mt-4 w-full"><x-heroicon-o-shopping-cart class="size-5" /> Add to cart</button>
                        </form>
                        @else
                            <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">This product is not currently available for purchase.</div>
                        @endif
                    </div>
                    <div class="mt-3 flex justify-end">
                        @auth
                            @php($wishlistItem = auth()->user()->wishlistItems()->where('product_id', $product->id)->first())
                            @if ($wishlistItem)
                                <form method="POST" action="{{ route('wishlist.destroy', $wishlistItem) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="grid size-11 place-items-center rounded-lg border border-teal-300 bg-teal-50 text-teal-700" aria-label="Remove from wishlist"><x-heroicon-s-heart class="size-5" /></button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('wishlist.store', $product) }}">
                                    @csrf
                                    <button class="grid size-11 place-items-center rounded-lg border border-zinc-300 hover:bg-zinc-50" aria-label="Add to wishlist"><x-heroicon-o-heart class="size-5" /></button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('wishlist.index') }}" class="grid size-11 place-items-center rounded-lg border border-zinc-300 hover:bg-zinc-50" aria-label="Wishlist"><x-heroicon-o-heart class="size-5" /></a>
                        @endauth
                    </div>
                    @if ($product->billing_model->value === 'manual_subscription')
                        <p class="mt-3 text-center text-xs font-semibold text-zinc-500">Manual {{ $product->billing_interval?->label() }} renewal · cancel by not renewing</p>
                    @endif
                    <p class="mt-3 flex items-center justify-center gap-2 text-xs font-semibold text-zinc-500">
                        <x-heroicon-o-key class="size-4" /> License delivered automatically after payment
                    </p>
                </div>
            </div>
        </section>
        <section class="mt-12 grid gap-10 border-t border-zinc-200 pt-10 lg:grid-cols-[1fr_300px]">
            <div>
                <h2 class="text-2xl font-extrabold">About this software</h2>
                <div class="mt-5 whitespace-pre-line text-base leading-8 text-zinc-600">{{ $product->description }}</div>
            </div>
            <aside class="border-l border-zinc-200 pl-7 text-sm">
                <h3 class="font-extrabold">Why MerebHub?</h3>
                <ul class="mt-4 grid gap-3 text-zinc-600">
                    <li class="flex gap-2"><x-heroicon-o-check class="size-5 text-teal-600" /> Secure Chapa checkout</li>
                    <li class="flex gap-2"><x-heroicon-o-check class="size-5 text-teal-600" /> Immediate license key</li>
                    <li class="flex gap-2"><x-heroicon-o-check class="size-5 text-teal-600" /> Official Partner</li>
                </ul>
            </aside>
        </section>
        @if ($relatedProducts->isNotEmpty())
            <section class="mt-14">
                <h2 class="mb-6 text-2xl font-extrabold">More in {{ $product->category }}</h2>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($relatedProducts as $related)
                        <x-product-card :product="$related" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
