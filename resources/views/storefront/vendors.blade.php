@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-[1500px] px-5 py-12 lg:px-8">
        <div class="max-w-3xl">
            <p class="text-sm font-extrabold uppercase text-teal-700">Ethiopian developers</p>
            <h1 class="mt-2 text-4xl font-extrabold text-zinc-950 sm:text-5xl">Meet the makers behind the software</h1>
            <p class="mt-4 max-w-2xl leading-7 text-zinc-600">Discover independent developers, studios, and publishers building practical software in Ethiopia.</p>
        </div>

        <form method="GET" class="mt-10 grid gap-3 border-y border-zinc-200 py-5 md:grid-cols-[minmax(0,1fr)_auto_auto_auto]">
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

        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($vendors as $vendor)
                <a href="{{ route('vendors.show', $vendor) }}" class="group rounded-xl border border-zinc-200 p-5 transition hover:-translate-y-0.5 hover:border-teal-300 hover:shadow-lg">
                    <div class="flex items-start gap-4">
                        @if ($vendor->avatarUrl())
                            <img src="{{ $vendor->avatarUrl() }}" alt="" class="size-16 rounded-xl object-cover">
                        @else
                            <span class="grid size-16 place-items-center rounded-xl bg-teal-50 text-teal-700"><x-heroicon-o-building-office-2 class="size-7" /></span>
                        @endif
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <h2 class="truncate font-extrabold text-zinc-950 group-hover:text-teal-700">{{ $vendor->name }}</h2>
                                @if ($vendor->is_verified)<x-heroicon-s-check-badge class="size-4 shrink-0 text-teal-600" />@endif
                            </div>
                            <p class="mt-1 line-clamp-2 text-sm leading-5 text-zinc-500">{{ $vendor->tagline ?: $vendor->bio }}</p>
                        </div>
                    </div>
                    <div class="mt-5 flex gap-4 border-t border-zinc-100 pt-4 text-xs font-bold text-zinc-600">
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
