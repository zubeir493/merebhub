@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-[1400px] px-5 py-10 lg:px-8 lg:py-14">
        <section class="overflow-hidden border border-zinc-200 bg-white">
            <div class="h-40 bg-zinc-950 sm:h-56">
                @if ($author->coverUrl())<img src="{{ $author->coverUrl() }}" alt="" class="h-full w-full object-cover">@endif
            </div>
            <div class="px-5 pb-7 sm:px-8">
                <div class="-mt-12 flex flex-col gap-5 sm:flex-row sm:items-end">
                    @if ($author->avatarUrl())
                        <img src="{{ $author->avatarUrl() }}" alt="{{ $author->name }}" class="size-24 rounded-md bg-white object-cover ring-4 ring-white outline outline-1 outline-black/10">
                    @else
                        <span class="grid size-24 place-items-center rounded-md bg-teal-50 text-teal-700 ring-4 ring-white"><x-heroicon-o-building-office-2 class="size-10" /></span>
                    @endif
                    <div class="flex-1 sm:pt-14">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-4xl font-extrabold leading-none tracking-[-0.045em] text-zinc-950 text-balance sm:text-5xl">{{ $author->name }}</h1>
                            @if ($author->is_verified)
                                <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2.5 py-1 text-xs font-extrabold text-teal-700"><x-heroicon-s-check-badge class="size-4" /> Verified</span>
                            @endif
                        </div>
                        @if ($author->tagline)<p class="mt-2 max-w-2xl leading-7 text-zinc-600 text-pretty">{{ $author->tagline }}</p>@endif
                    </div>
                    <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm font-bold text-zinc-600 tabular-nums">
                        <span>{{ number_format($products->total()) }} products</span>
                        <span>★ {{ number_format((float) $author->average_rating, 1) }}</span>
                        @if ($author->show_public_sales)<span>{{ number_format($author->public_sales_count) }} sales</span>@endif
                    </div>
                </div>

                <div class="mt-6 grid gap-6 border-t border-zinc-100 pt-6 lg:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
                    <p class="leading-7 text-zinc-600 text-pretty">{{ $author->bio }}</p>
                    <div class="grid content-start gap-2 text-sm text-zinc-600">
                        @if ($author->location)<span class="flex items-center gap-2"><x-heroicon-o-map-pin class="size-4" /> {{ $author->location }}</span>@endif
                        @if ($author->member_since)<span class="flex items-center gap-2"><x-heroicon-o-calendar class="size-4" /> Member since {{ $author->member_since->format('M Y') }}</span>@endif
                        @if ($author->website_url)<a href="{{ $author->website_url }}" rel="noopener noreferrer" target="_blank" class="flex items-center gap-2 font-bold text-teal-700"><x-heroicon-o-globe-alt class="size-4" /> Website</a>@endif
                        @if ($author->support_url)<a href="{{ $author->support_url }}" rel="noopener noreferrer" target="_blank" class="flex items-center gap-2 font-bold text-teal-700"><x-heroicon-o-lifebuoy class="size-4" /> Support</a>@endif
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-10">
            <div class="flex flex-col gap-4 border-b border-zinc-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-4xl font-extrabold tracking-[-0.04em] text-zinc-950">Software by {{ $author->name }}</h2>
                </div>
                <form method="GET" class="grid w-full gap-2 sm:grid-cols-2 lg:w-auto lg:grid-cols-[13rem_11rem_10rem_auto]">
                    <input name="q" value="{{ $search }}" placeholder="Search this vendor" class="form-input w-full">
                    <select name="category" class="form-input w-full" aria-label="Category">
                        <option value="">All categories</option>
                        @foreach ($categories as $item)<option value="{{ $item }}" @selected($category === $item)>{{ $item }}</option>@endforeach
                    </select>
                    <select name="sort" class="form-input w-full" aria-label="Sort products">
                        <option value="newest" @selected($sort === 'newest')>Newest</option>
                        <option value="popular" @selected($sort === 'popular')>Popularity</option>
                        <option value="rating" @selected($sort === 'rating')>Rating</option>
                        <option value="price" @selected($sort === 'price')>Price</option>
                    </select>
                    <button class="btn-dark">Filter</button>
                </form>
            </div>

            <div class="mt-7 grid gap-x-5 gap-y-9 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse ($products as $product)
                    <x-product-card :product="$product" />
                @empty
                    <div class="col-span-full border-y border-zinc-200 py-16 text-center">
                        <x-heroicon-o-cube class="mx-auto size-9 text-zinc-400" />
                        <h3 class="mt-4 font-extrabold text-zinc-900">No published software yet</h3>
                    </div>
                @endforelse
            </div>
            <div class="mt-10">{{ $products->links() }}</div>
        </section>
    </div>
@endsection
