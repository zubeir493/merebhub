@extends('layouts.storefront')

@section('content')
    @php
        $hasActiveFilters = $search !== '' || $category !== '' || $platform !== '' || $sort !== $defaultSort;
        $activeFilterCount = collect([$search, $category, $platform])->filter()->count() + ($sort !== $defaultSort ? 1 : 0);
    @endphp

    <section class="relative isolate overflow-hidden bg-zinc-50">
        <x-ambient-lines class="absolute -right-20 -top-36 -z-10 h-[36rem] w-[min(60vw,52rem)] opacity-60" />
        <div class="relative z-10 mx-auto grid max-w-[1400px] gap-8 px-5 py-16 lg:grid-cols-12 lg:items-end lg:px-8 lg:py-24">
            <div class="lg:col-span-8">
                <h1 class="max-w-[12ch] text-[clamp(3.4rem,6.5vw,5.75rem)] font-extrabold leading-[.94] tracking-[-0.035em] text-zinc-950 text-balance">{{ $heading }}</h1>
            </div>
            <div class="lg:col-span-4 lg:pb-1">
                <p class="max-w-md text-base font-medium leading-8 text-zinc-600 text-pretty">{{ $description }}</p>
            </div>
        </div>
    </section>

    <div data-store-catalog class="mx-auto max-w-[1400px] px-5 py-10 transition-opacity lg:px-8 lg:py-16">
        <div class="flex items-start rounded-xl bg-zinc-50 px-4 lg:hidden">
            <details data-mobile-catalog-filters class="group min-w-0 flex-1">
                <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 text-sm font-extrabold text-zinc-950">
                    <span class="flex items-center gap-2.5">
                        <x-heroicon-o-adjustments-horizontal class="size-5 text-teal-700" />
                        Filters
                        @if ($activeFilterCount > 0)
                            <span class="grid size-5 place-items-center rounded-full bg-teal-100 text-[10px] text-teal-800">{{ $activeFilterCount }}</span>
                        @endif
                    </span>
                    <x-heroicon-o-chevron-down class="size-4 text-zinc-400 transition-transform duration-200 group-open:rotate-180" />
                </summary>
                <x-store-filters
                    :route-name="$routeName"
                    :search="$search"
                    :category="$category"
                    :platform="$platform"
                    :sort="$sort"
                    :categories="$categories"
                    :platforms="$platforms"
                    class="pb-7 pt-3"
                />
            </details>
            @if ($hasActiveFilters)
                <a href="{{ route($routeName) }}" data-catalog-reset class="ml-2 mt-2.5 grid size-9 shrink-0 place-items-center rounded-lg text-zinc-500 transition hover:bg-white hover:text-teal-700 focus-visible:ring-4 focus-visible:ring-teal-500/10" aria-label="Reset catalog filters" title="Reset filters">
                    <x-heroicon-o-arrow-path class="size-4.5" />
                </a>
            @endif
        </div>

        <div class="mt-10 grid gap-10 lg:mt-0 lg:grid-cols-[15rem_minmax(0,1fr)] xl:gap-16">
            <aside class="hidden lg:block" aria-label="Catalog filters">
                <div class="sticky top-28">
                    <div class="mb-7 flex min-h-9 items-center justify-between gap-3">
                        <h2 class="text-lg font-extrabold text-zinc-950">Filters</h2>
                        @if ($hasActiveFilters)
                            <a href="{{ route($routeName) }}" data-catalog-reset class="grid size-9 place-items-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 hover:text-teal-700 focus-visible:ring-4 focus-visible:ring-teal-500/10" aria-label="Reset catalog filters" title="Reset filters">
                                <x-heroicon-o-arrow-path class="size-4.5" />
                            </a>
                        @endif
                    </div>
                    <x-store-filters
                        :route-name="$routeName"
                        :search="$search"
                        :category="$category"
                        :platform="$platform"
                        :sort="$sort"
                        :categories="$categories"
                        :platforms="$platforms"
                    />
                </div>
            </aside>

            <main class="min-w-0">
                <div class="flex min-h-11 items-center justify-between gap-5">
                    <p class="text-sm font-bold text-zinc-500">
                        @if ($search !== '')
                            <span class="text-zinc-950">“{{ $search }}”</span>
                            <span class="mx-1.5 text-zinc-300">·</span>
                        @endif
                        {{ number_format($products->total()) }} {{ Str::plural('result', $products->total()) }}
                    </p>
                    <form action="{{ route($routeName) }}" method="GET" data-catalog-sort-form class="shrink-0">
                        @if ($search !== '')
                            <input type="hidden" name="q" value="{{ $search }}">
                        @endif
                        @if ($category !== '')
                            <input type="hidden" name="category" value="{{ $category }}">
                        @endif
                        @if ($platform !== '')
                            <input type="hidden" name="platform" value="{{ $platform }}">
                        @endif
                        <label for="catalog-sort" class="sr-only">Sort products</label>
                        <select id="catalog-sort" name="sort" class="form-input h-10 w-auto min-w-36 py-0 pl-3 pr-9 text-xs font-bold">
                            <option value="popular" @selected($sort === 'popular')>Most popular</option>
                            <option value="newest" @selected($sort === 'newest')>Newest</option>
                            <option value="rating" @selected($sort === 'rating')>Highest rated</option>
                            <option value="price_asc" @selected($sort === 'price_asc')>Price: low to high</option>
                            <option value="price_desc" @selected($sort === 'price_desc')>Price: high to low</option>
                        </select>
                    </form>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-11 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($products as $product)
                        <x-product-card :product="$product" />
                    @empty
                        <section class="col-span-full rounded-xl bg-zinc-50 px-6 py-20 text-center">
                            <span class="mx-auto grid size-14 place-items-center rounded-full bg-teal-50 text-teal-700"><x-heroicon-o-magnifying-glass class="size-6" /></span>
                            <h3 class="mt-6 text-2xl font-extrabold text-zinc-950">Nothing matched that combination.</h3>
                            <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-zinc-500">Try a broader search, choose fewer filters, or return to the full catalog.</p>
                            <a href="{{ route($routeName) }}" class="btn-dark mt-7">Reset the catalog</a>
                        </section>
                    @endforelse
                </div>

                <div class="mt-14">{{ $products->links() }}</div>

                @if ($authors->isNotEmpty())
                    <section class="mt-16">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <h2 class="max-w-xl text-3xl font-extrabold tracking-[-0.025em] text-zinc-950">Developers matching “{{ $search }}”</h2>
                            <a href="{{ route('vendors.index', ['q' => $search]) }}" class="group inline-flex items-center gap-2 text-sm font-extrabold text-teal-700">See all developers <x-heroicon-o-arrow-right class="size-4 transition-transform group-hover:translate-x-1" /></a>
                        </div>
                        <div class="mt-7 grid gap-3 sm:grid-cols-2">
                            @foreach ($authors as $author)
                                <a href="{{ route('vendors.show', $author) }}" class="group flex min-h-32 items-center gap-4 rounded-xl bg-zinc-50 p-5 transition hover:bg-zinc-100">
                                    @if ($author->avatarUrl())
                                        <img src="{{ $author->avatarUrl() }}" alt="" class="size-14 rounded-xl object-cover outline outline-1 -outline-offset-1 outline-black/10">
                                    @else
                                        <span class="grid size-14 shrink-0 place-items-center rounded-xl bg-teal-50 text-teal-700"><x-heroicon-o-building-office-2 class="size-7" /></span>
                                    @endif
                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-center gap-1.5 font-extrabold text-zinc-950 transition group-hover:text-teal-700">{{ $author->name }} @if ($author->is_verified)<x-heroicon-s-check-badge class="size-4 shrink-0 text-teal-600" />@endif</span>
                                        <span class="mt-1 line-clamp-2 block text-xs leading-5 text-zinc-500">{{ $author->tagline }}</span>
                                        <span class="mt-2 block text-[11px] font-bold tabular-nums text-zinc-500">{{ $author->products_count }} {{ Str::plural('product', $author->products_count) }}</span>
                                    </span>
                                    <x-heroicon-o-arrow-up-right class="size-4 shrink-0 text-zinc-300 transition group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-teal-700" />
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </main>
        </div>
    </div>
@endsection
