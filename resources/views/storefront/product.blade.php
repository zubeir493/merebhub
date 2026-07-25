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
                <a href="{{ route('authors.show', $product->author) }}" class="mt-5 inline-flex items-center gap-2 text-sm font-extrabold text-teal-700">
                    By {{ $product->author->name }}
                    <x-heroicon-o-check-badge class="size-4" />
                </a>
                <div class="mt-5 flex items-center gap-2 text-sm">
                    <x-heroicon-s-star class="size-5 text-amber-400" />
                    <strong>{{ number_format((float) $product->rating, 1) }}</strong>
                    <span class="text-zinc-500">{{ number_format($product->ratings_count) }} ratings</span>
                </div>
                <div class="mt-8 border-y border-zinc-200 py-6">
                    <div class="flex items-end gap-3">
                        <strong class="text-3xl text-zinc-950">{{ number_format((float) $product->price) }} ETB</strong>
                        @if ($product->compare_at_price)
                            <span class="pb-1 text-sm font-semibold text-zinc-400 line-through">{{ number_format((float) $product->compare_at_price) }} ETB</span>
                        @endif
                    </div>
                    <a href="{{ route('checkout.show', $product) }}" class="btn-primary mt-5 w-full">
                        <x-heroicon-o-shopping-bag class="size-5" />
                        Buy now
                    </a>
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
                <h3 class="font-extrabold">What you get</h3>
                <ul class="mt-4 grid gap-3 text-zinc-600">
                    <li class="flex gap-2"><x-heroicon-o-check class="size-5 text-teal-600" /> Secure Chapa checkout</li>
                    <li class="flex gap-2"><x-heroicon-o-check class="size-5 text-teal-600" /> Immediate license key</li>
                    <li class="flex gap-2"><x-heroicon-o-check class="size-5 text-teal-600" /> Latest build download</li>
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
