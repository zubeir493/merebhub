@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-[1400px] px-5 py-10 lg:px-8 lg:py-14">
        <div class="max-w-3xl">
            <p class="text-sm font-extrabold uppercase text-teal-700">Search MerebHub</p>
            <h1 class="mt-2 text-3xl font-extrabold text-balance sm:text-4xl">
                {{ $search === '' ? 'Find software built for the way you work' : "Results for “{$search}”" }}
            </h1>
            <form action="{{ route('search') }}" role="search" class="relative mt-7">
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-zinc-400" />
                <input
                    name="q"
                    value="{{ $search }}"
                    aria-label="Search MerebHub"
                    placeholder="Try inventory, design, games, or a maker"
                    class="h-14 w-full rounded-xl border border-zinc-300 bg-white pl-12 pr-32 text-base outline-none shadow-sm transition-[border-color,box-shadow] focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10"
                >
                <button class="btn-primary absolute right-1.5 top-1.5 h-11">Search</button>
            </form>
        </div>

        @if ($search === '')
            <section class="mt-12 rounded-2xl bg-zinc-50 px-6 py-12 text-center ring-1 ring-zinc-950/5">
                <span class="mx-auto grid size-14 place-items-center rounded-full bg-white shadow-sm"><x-heroicon-o-magnifying-glass class="size-7 text-zinc-500" /></span>
                <h2 class="mt-5 text-xl font-extrabold">What are you looking for?</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-zinc-500">Search by app, category, or Ethiopian software maker.</p>
                <a href="{{ route('store.index') }}" class="btn-dark mt-6">Browse the catalog</a>
            </section>
        @else
            <div class="mt-12 flex flex-wrap items-end justify-between gap-3 border-b border-zinc-200 pb-5">
                <div>
                    <h2 class="mt-1 text-2xl font-extrabold">{{ number_format($products->total()) }} {{ Str::plural('result', $products->total()) }}</h2>
                </div>
                @if ($products->total() > 0)
                    <p class="text-sm text-zinc-500">Showing the most popular matches first</p>
                @endif
            </div>

            <div class="mt-7 grid gap-x-5 gap-y-9 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse ($products as $product)
                    <x-product-card :product="$product" />
                @empty
                    <div class="col-span-full rounded-2xl bg-zinc-50 px-6 py-14 text-center ring-1 ring-zinc-950/5">
                        <x-heroicon-o-cube class="mx-auto size-9 text-zinc-400" />
                        <h3 class="mt-4 text-lg font-extrabold">No software matched “{{ $search }}”</h3>
                        <p class="mt-2 text-sm text-zinc-500">Try a broader term or browse the full catalog.</p>
                        <a href="{{ route('store.index') }}" class="btn-dark mt-6">Browse all software</a>
                    </div>
                @endforelse
            </div>
            <div class="mt-10">{{ $products->links() }}</div>

            @if ($authors->isNotEmpty())
                <section class="mt-14 border-t border-zinc-200 pt-10">
                    <h2 class="mt-1 text-2xl font-extrabold">Developers matching “{{ $search }}”</h2>
                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($authors as $author)
                            <a href="{{ route('vendors.show', $author) }}" class="group flex min-h-28 items-center gap-4 rounded-2xl bg-white p-4 shadow-[0_1px_2px_oklch(0_0_0/0.06),0_8px_24px_oklch(0_0_0/0.05)] ring-1 ring-zinc-950/5 transition-transform active:scale-[0.96]">
                                @if ($author->avatarUrl())
                                    <img src="{{ $author->avatarUrl() }}" alt="" class="size-16 rounded-xl object-cover outline outline-1 -outline-offset-1 outline-black/10">
                                @else
                                    <span class="grid size-16 shrink-0 place-items-center rounded-xl bg-teal-50 text-teal-700"><x-heroicon-o-building-office-2 class="size-8" /></span>
                                @endif
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-1.5 font-extrabold group-hover:text-teal-700">{{ $author->name }} @if ($author->is_verified)<x-heroicon-s-check-badge class="size-4 shrink-0 text-teal-600" />@endif</span>
                                    <span class="mt-1 line-clamp-2 block text-sm leading-5 text-zinc-500">{{ $author->tagline }}</span>
                                    <span class="mt-2 block text-xs font-bold text-zinc-500 tabular-nums">{{ $author->products_count }} {{ Str::plural('product', $author->products_count) }}</span>
                                </span>
                                <x-heroicon-o-arrow-right class="size-5 shrink-0 text-zinc-400 group-hover:text-teal-700" />
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
@endsection
