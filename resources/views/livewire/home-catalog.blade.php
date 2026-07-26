<div>
    <div class="border-b border-zinc-200 bg-white">
        <div class="mx-auto flex max-w-[1500px] gap-7 overflow-x-auto px-5 py-4 text-sm font-bold text-zinc-700 lg:px-8">
            @foreach ($categories as $item)
                <button
                    wire:key="category-{{ Str::slug($item) }}"
                    wire:click="$set('category', '{{ $item }}')"
                    class="flex shrink-0 items-center gap-2 hover:text-teal-700 {{ $category === $item ? 'text-teal-700' : '' }}"
                >
                    @switch($item)
                        @case('Developer tools') <x-heroicon-o-code-bracket class="size-5" /> @break
                        @case('Business') <x-heroicon-o-briefcase class="size-5" /> @break
                        @case('Design') <x-heroicon-o-pencil-square class="size-5" /> @break
                        @case('Security') <x-heroicon-o-shield-check class="size-5" /> @break
                        @case('Games') <x-heroicon-o-puzzle-piece class="size-5" /> @break
                        @default <x-heroicon-o-squares-2x2 class="size-5" />
                    @endswitch
                    {{ $item }}
                </button>
            @endforeach
            <a href="{{ route('store.index') }}" class="ml-auto flex shrink-0 items-center gap-2 hover:text-teal-700">
                <x-heroicon-o-view-columns class="size-5" />
                View all
            </a>
        </div>
    </div>

    <section class="relative mx-auto h-[400px] w-full overflow-hidden bg-zinc-950 sm:h-[390px] lg:h-auto lg:min-h-[300px]">
        <img src="{{ asset('images/marketplace/hero-built-here.webp') }}" alt="Ethiopian software marketplace" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-zinc-950/90 via-zinc-950/50 to-transparent"></div>
        <div class="relative flex h-full max-w-[1500px] items-center px-5 py-12 lg:px-12">
            <div class="max-w-xl text-white">
                <p class="mb-3 text-sm font-extrabold uppercase text-teal-300">Built here. Ready for everywhere.</p>
                <h1 class="text-4xl font-extrabold leading-tight sm:text-5xl">Ethiopia's home for remarkable software</h1>
                <p class="mt-4 max-w-lg text-base leading-7 text-zinc-200">Curated apps, tools, and games from independent makers. Secure checkout and instant license delivery included.</p>
                <a href="{{ route('store.index') }}" class="mt-7 inline-flex items-center gap-2 rounded-lg bg-teal-400 px-5 py-3 text-sm font-extrabold text-zinc-950 hover:bg-teal-300">
                    Explore Now
                    <x-heroicon-o-arrow-right class="size-4" />
                </a>
            </div>
        </div>
    </section>

    @if ($deals->isNotEmpty())
        <section class="border-b border-zinc-200 bg-white">
            <div class="mx-auto grid max-w-[1500px] grid-cols-1 divide-y divide-zinc-200 sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-5">
                @foreach ($deals as $index => $deal)
                    <a wire:key="deal-{{ $deal->id }}" href="{{ route('products.show', $deal) }}" class="flex items-center gap-3 px-5 py-4 hover:bg-zinc-50">
                        <img src="{{ $deal->coverUrl() }}" alt="" class="size-12 rounded-lg object-cover">
                        <span class="min-w-0 flex-1">
                            <strong class="block truncate text-sm text-zinc-950">{{ $deal->name }}</strong>
                            <small class="block truncate text-xs text-zinc-500">{{ $deal->author->name }}</small>
                        </span>
                        <strong class="text-xs text-rose-600">{{ number_format((float) $deal->price) }} ETB</strong>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mx-auto max-w-[1500px] px-5 py-10 lg:px-8">
        @if ($topProducts->isNotEmpty())
            <section>
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-2xl font-extrabold text-zinc-950">Top selling this week</h2>
                    <a href="{{ route('store.bestsellers') }}" class="text-sm font-extrabold text-teal-700 hover:text-teal-900">See all</a>
                </div>
                <div class="grid gap-x-12 gap-y-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($topProducts as $index => $product)
                        <a wire:key="top-{{ $product->id }}" href="{{ route('products.show', $product) }}" class="group flex min-w-0 items-center gap-3">
                            <span class="w-5 text-sm font-bold text-zinc-400">{{ $index + 1 }}</span>
                            <img src="{{ $product->coverUrl() }}" alt="" class="size-20 shrink-0 rounded-lg object-cover">
                            <span class="min-w-0">
                                <strong class="block truncate text-base text-zinc-950 group-hover:text-teal-700">{{ $product->name }}</strong>
                                <small class="mt-1 block truncate text-xs text-zinc-500">
                                    <span class="font-bold text-zinc-700">★ {{ number_format((float) $product->rating, 1) }}</span>
                                    · {{ $product->category }} · {{ $product->platforms->pluck('name')->join(', ') }}
                                </small>
                                <span class="mt-1 block text-xs font-bold text-zinc-700">{{ number_format((float) $product->price) }} ETB</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($featured->isNotEmpty())
            <section class="mt-12">
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-2xl font-extrabold text-zinc-950">Made in Ethiopia</h2>
                    <span class="text-sm font-semibold text-zinc-500">Editor-selected releases</span>
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($featured as $product)
                        <x-product-card wire:key="featured-{{ $product->id }}" :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif

        <section id="catalog" class="mt-14 scroll-mt-28">
            <div class="flex flex-col gap-5 border-b border-zinc-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-extrabold uppercase text-teal-700">The catalog</p>
                    <h2 class="mt-1 text-3xl font-extrabold text-zinc-950">Find your next essential tool</h2>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($platforms as $item)
                        <button
                            wire:key="platform-{{ $item->id }}"
                            wire:click="$set('platform', '{{ $item->slug }}')"
                            class="rounded-lg border px-3 py-2 text-xs font-bold {{ $platform === $item->slug ? 'border-teal-500 bg-teal-50 text-teal-800' : 'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-400' }}"
                        >{{ $item->name }}</button>
                    @endforeach
                </div>
            </div>

            <div wire:loading.class="opacity-60" class="mt-7 grid grid-cols-1 gap-x-5 gap-y-9 transition sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse ($products as $product)
                    <x-product-card wire:key="catalog-{{ $product->id }}" :product="$product" />
                @empty
                    <div class="col-span-full border-y border-zinc-200 py-16 text-center">
                        <x-heroicon-o-magnifying-glass class="mx-auto size-8 text-zinc-400" />
                        <h3 class="mt-4 font-extrabold text-zinc-900">No software matches those filters</h3>
                        <button wire:click="clearFilters" class="mt-3 text-sm font-bold text-teal-700">Clear filters</button>
                    </div>
                @endforelse
            </div>
            <div class="mt-10">{{ $products->links() }}</div>
        </section>
    </div>
</div>
