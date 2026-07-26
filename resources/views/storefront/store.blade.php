@extends('layouts.storefront')

@section('content')
    <div class="border-b border-zinc-200 bg-zinc-950 text-white">
        <div class="mx-auto max-w-[1500px] px-5 py-10 lg:px-8 lg:py-14">
            <p class="text-sm font-extrabold uppercase text-teal-300">MerebHub Store</p>
            <div class="mt-2 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <h1 class="text-4xl font-extrabold text-balance sm:text-5xl">{{ $heading }}</h1>
                    <p class="mt-3 max-w-xl leading-7 text-zinc-300 text-pretty">{{ $description }}</p>
                </div>
                <div class="flex items-center gap-2 text-sm font-bold text-zinc-300">
                    <x-heroicon-o-shield-check class="size-5 text-teal-300" />
                    Curated and reviewed before publishing
                </div>
            </div>
        </div>
    </div>

    <div class="border-b border-zinc-200 bg-white">
        <nav class="mx-auto flex max-w-[1500px] gap-2 overflow-x-auto px-5 py-3 lg:px-8" aria-label="Store collections">
            @foreach ([
                ['all', 'store.index', 'All software'],
                ['newarrivals', 'store.newarrivals', 'New arrivals'],
                ['bestsellers', 'store.bestsellers', 'Best sellers'],
                ['deals', 'store.deals', 'Deals'],
            ] as [$key, $route, $label])
                <a
                    href="{{ route($route) }}"
                    @class([
                        'flex min-h-11 shrink-0 items-center rounded-lg px-4 text-sm font-extrabold transition-[color,background-color,transform] active:scale-[0.96]',
                        'bg-teal-50 text-teal-800' => $collection === $key,
                        'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950' => $collection !== $key,
                    ])
                    @if ($collection === $key) aria-current="page" @endif
                >{{ $label }}</a>
            @endforeach
        </nav>
    </div>

    <div class="mx-auto max-w-[1500px] px-5 py-8 lg:px-8 lg:py-10">
        <form action="{{ route($routeName) }}" method="GET" x-data="{ openSort: false, openFilters: false }" @click.away="openSort = false; openFilters = false" class="relative rounded-2xl bg-zinc-50 p-4 ring-1 ring-zinc-950/5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <label class="relative flex-1">
                    <span class="sr-only">Search software</span>
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-zinc-400" />
                    <input name="q" value="{{ $search }}" placeholder="Search apps, categories, or makers" class="form-input h-12 w-full rounded-2xl pl-11 pr-4">
                </label>

                <div class="flex items-center gap-2">
                    <div class="relative">
                        <button
                            type="button"
                            x-on:click="openSort = !openSort; openFilters = false"
                            class="inline-flex h-12 items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 text-sm font-bold text-zinc-700 shadow-sm transition hover:border-zinc-300 hover:text-zinc-900"
                        >
                            <x-heroicon-o-arrows-up-down class="size-5" />
                            Sort
                        </button>

                        <div x-cloak x-show="openSort" x-transition class="absolute right-0 z-20 mt-2 w-72 overflow-hidden rounded-3xl border border-zinc-200 bg-white p-4 shadow-xl">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-500">Sort by</p>
                            <select name="sort" class="form-input mt-3 w-full">
                                <option value="popular" @selected($sort === 'popular')>Most popular</option>
                                <option value="newest" @selected($sort === 'newest')>Newest</option>
                                <option value="rating" @selected($sort === 'rating')>Highest rated</option>
                                <option value="price_asc" @selected($sort === 'price_asc')>Price: low to high</option>
                                <option value="price_desc" @selected($sort === 'price_desc')>Price: high to low</option>
                            </select>
                        </div>
                    </div>

                    <div class="relative">
                        <button
                            type="button"
                            x-on:click="openFilters = !openFilters; openSort = false"
                            class="inline-flex h-12 items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 text-sm font-bold text-zinc-700 shadow-sm transition hover:border-zinc-300 hover:text-zinc-900"
                        >
                            <x-heroicon-o-funnel class="size-5" />
                            Filters
                        </button>

                        <div x-cloak x-show="openFilters" x-transition class="absolute right-0 z-20 mt-2 w-[28rem] overflow-hidden rounded-3xl border border-zinc-200 bg-white p-5 shadow-xl">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="block">
                                    <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.24em] text-zinc-500">Category</span>
                                    <select name="category" class="form-input w-full">
                                        <option value="">All categories</option>
                                        @foreach ($categories as $item)
                                            <option value="{{ $item }}" @selected($category === $item)>{{ $item }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="block">
                                    <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.24em] text-zinc-500">Platform</span>
                                    <select name="platform" class="form-input w-full">
                                        <option value="">All platforms</option>
                                        @foreach ($platforms as $item)
                                            <option value="{{ $item->slug }}" @selected($platform === $item->slug)>{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>

                            <div class="mt-5 flex flex-wrap items-center gap-3">
                                <button type="submit" class="inline-flex min-w-[10rem] items-center justify-center rounded-2xl bg-teal-700 px-4 py-3 text-sm font-extrabold text-white transition hover:bg-teal-600">Apply filters</button>

                                @if ($search !== '' || $category !== '' || $platform !== '' || $sort !== 'popular')
                                    <a href="{{ route($routeName) }}" class="inline-flex min-w-[9rem] items-center justify-center rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-bold text-zinc-700 hover:bg-zinc-50">Reset</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="mt-8 flex flex-wrap items-end justify-between gap-4 border-b border-zinc-200 pb-5">
            <div>
                <h2 class="mt-1 text-2xl font-extrabold text-zinc-950">
                    {{ number_format($products->total()) }} {{ Str::plural('product', $products->total()) }}
                </h2>
            </div>
            @if ($search !== '' || $category !== '' || $platform !== '' || $sort !== 'popular')
                <a href="{{ route($routeName) }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg px-3 text-sm font-bold text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950">
                    <x-heroicon-o-x-mark class="size-4" />
                    Clear filters
                </a>
            @endif
        </div>

        <div class="mt-7 grid grid-cols-1 gap-x-5 gap-y-10 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($products as $product)
                <x-product-card :product="$product" />
            @empty
                <section class="col-span-full rounded-2xl bg-zinc-50 px-6 py-16 text-center ring-1 ring-zinc-950/5">
                    <span class="mx-auto grid size-14 place-items-center rounded-full bg-white shadow-sm"><x-heroicon-o-magnifying-glass class="size-7 text-zinc-500" /></span>
                    <h3 class="mt-5 text-lg font-extrabold">No software matches those filters</h3>
                    <p class="mt-2 text-sm text-zinc-500">Try a broader search or clear your filters.</p>
                    <a href="{{ route($routeName) }}" class="btn-dark mt-6">Clear filters</a>
                </section>
            @endforelse
        </div>

        <div class="mt-12">{{ $products->links() }}</div>
    </div>
@endsection
