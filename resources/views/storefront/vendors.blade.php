@extends('layouts.storefront')

@section('content')
    <section data-public-page-header class="public-page-header">
        <div class="mx-auto grid max-w-[1400px] gap-8 px-5 py-14 sm:py-16 lg:grid-cols-12 lg:items-end lg:px-8 lg:py-20">
            <h1 class="public-page-title lg:col-span-8">Meet the makers behind the software.</h1>
            <p class="public-lede lg:col-span-4">Discover independent developers, studios, and publishers building practical software in Ethiopia.</p>
        </div>
    </section>

    <div class="mx-auto max-w-[1400px] px-5 py-12 lg:px-8 lg:py-16">
        <form method="GET" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto_auto_auto]">
            <label class="relative">
                <span class="sr-only">Search developers</span>
                <x-heroicon-o-magnifying-glass class="absolute left-4 top-1/2 size-5 -translate-y-1/2 text-zinc-400" />
                <input name="q" value="{{ $search }}" placeholder="Search developers" class="form-input pl-11">
            </label>
            <select name="sort" class="form-input" aria-label="Sort developers">
                <option value="newest" @selected($sort === 'newest')>Newest</option>
                <option value="products" @selected($sort === 'products')>Most products</option>
                <option value="sales" @selected($sort === 'sales')>Most sales</option>
                <option value="rating" @selected($sort === 'rating')>Highest rated</option>
            </select>
            <label class="flex items-center gap-2 rounded-lg border border-zinc-300 px-4 text-sm font-bold">
                <input type="checkbox" name="verified" value="1" @checked(request()->boolean('verified')) class="size-4 rounded border-zinc-300 text-teal-600">
                Verified
            </label>
            <button class="btn-dark">Apply filters</button>
        </form>

        <div class="mt-10 grid border-t border-zinc-200 md:grid-cols-2">
            @forelse ($vendors as $vendor)
                <a href="{{ route('vendors.show', $vendor) }}" class="group border-b border-zinc-200 py-7 transition hover:bg-zinc-50 md:px-7 md:odd:border-r md:odd:pl-0 md:even:pr-0">
                    <div class="flex items-start gap-4">
                        @if ($vendor->avatarUrl())
                            <img src="{{ $vendor->avatarUrl() }}" alt="" loading="lazy" class="size-16 rounded-md object-cover">
                        @else
                            <span class="grid size-16 place-items-center rounded-md bg-teal-50 text-teal-700"><x-heroicon-o-building-office-2 class="size-7" /></span>
                        @endif
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <h2 class="truncate font-extrabold text-zinc-950 group-hover:text-teal-700">{{ $vendor->name }}</h2>
                                @if ($vendor->is_verified)<x-heroicon-s-check-badge class="size-4 shrink-0 text-teal-600" />@endif
                            </div>
                            <p class="mt-1 line-clamp-2 text-sm leading-5 text-zinc-500">{{ $vendor->tagline ?: $vendor->bio }}</p>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-x-4 gap-y-2 border-t border-zinc-100 pt-4 text-xs font-bold text-zinc-600 tabular-nums">
                        <span>{{ number_format($vendor->products_count) }} products</span>
                        <span>★ {{ number_format((float) $vendor->average_rating, 1) }}</span>
                        @if ($vendor->show_public_sales)<span>{{ number_format($vendor->public_sales_count) }} sales</span>@endif
                    </div>
                </a>
            @empty
                <div class="col-span-full border-y border-zinc-200 py-16 text-center">
                    <x-heroicon-o-user-group class="mx-auto size-9 text-zinc-400" />
                    <h2 class="mt-4 font-extrabold text-zinc-900">No developers match those filters</h2>
                </div>
            @endforelse
        </div>

        <div class="mt-10">{{ $vendors->links() }}</div>
    </div>
@endsection
