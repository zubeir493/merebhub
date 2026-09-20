@php
    $exhibitionProducts = $featured->take(4)->values();
    $popularProducts = $topProducts->take(6)->values();
    $newProducts = $products->take(4)->values();
@endphp

<div data-home class="overflow-hidden bg-white">
    <section data-public-page-header class="public-page-header">
        <div class="mx-auto max-w-[1400px] px-5 pb-12 pt-14 sm:pt-16 lg:px-8 lg:pb-14 lg:pt-20">
            <div class="grid gap-10 lg:grid-cols-12 lg:items-end">
                <h1 class="public-page-title lg:col-span-8">
                    Find the software worth keeping.
                </h1>
                <div class="lg:col-span-4 lg:pb-2">
                    <p class="public-lede">Independent Ethiopian software, selected with care and delivered through one trusted marketplace.</p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <a href="{{ route('store.index') }}" class="btn-primary">Browse software <x-heroicon-o-arrow-right class="size-4" /></a>
                        <a href="{{ route('vendors.index') }}" class="inline-flex items-center justify-center rounded-lg border border-zinc-300 bg-white px-5 py-3 text-sm font-extrabold text-zinc-700 transition hover:border-zinc-400 hover:text-zinc-950">Meet the developers</a>
                    </div>
                </div>
            </div>

            <div class="mt-12 grid border-y border-zinc-200 sm:grid-cols-3">
                <div class="flex items-center gap-3 py-4 sm:pr-5"><x-heroicon-o-shield-check class="size-5 shrink-0 text-teal-700" /><span class="text-xs font-bold text-zinc-600">Reviewed publishers</span></div>
                <div class="flex items-center gap-3 border-t border-zinc-200 py-4 sm:border-l sm:border-t-0 sm:px-5"><x-heroicon-o-banknotes class="size-5 shrink-0 text-teal-700" /><span class="text-xs font-bold text-zinc-600">Clear ETB pricing</span></div>
                <div class="flex items-center gap-3 border-t border-zinc-200 py-4 sm:border-l sm:border-t-0 sm:pl-5"><x-heroicon-o-key class="size-5 shrink-0 text-teal-700" /><span class="text-xs font-bold text-zinc-600">Purchases and licenses in one account</span></div>
            </div>
        </div>

        @if ($exhibitionProducts->isNotEmpty())
            <div class="mx-auto max-w-[1400px] px-5 pb-16 lg:px-8 lg:pb-20">
                <div data-home-exhibition class="grid overflow-hidden border border-zinc-200 bg-white lg:grid-cols-[minmax(300px,.72fr)_minmax(0,1.6fr)]">
                    <div class="order-2 flex flex-col border-zinc-200 lg:order-1 lg:border-r">
                        <div class="flex items-center justify-between px-5 py-5 lg:px-8">
                            <span class="text-xs font-extrabold text-zinc-600">Featured products</span>
                            <span class="text-xs font-bold tabular-nums text-zinc-400">{{ str_pad($exhibitionProducts->count(), 2, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="mt-auto">
                            @foreach ($exhibitionProducts as $product)
                                <a
                                    href="{{ route('products.show', $product) }}"
                                    data-exhibition-trigger="{{ $loop->index }}"
                                    aria-current="{{ $loop->first ? 'true' : 'false' }}"
                                    class="group relative isolate grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 overflow-hidden border-t border-zinc-200 px-5 py-5 transition duration-200 hover:bg-zinc-50 focus:bg-zinc-50 focus:outline-none focus:ring-4 focus:ring-inset focus:ring-teal-500/10 aria-[current=true]:bg-teal-50 aria-[current=true]:text-teal-900 lg:px-8"
                                >
                                    <span data-exhibition-progress aria-hidden="true" class="pointer-events-none absolute inset-0 z-0 origin-left bg-teal-100/70"></span>
                                    <span class="relative z-10 min-w-0">
                                        <strong class="block truncate text-base">{{ $product->name }}</strong>
                                        <span class="mt-1 block truncate text-xs font-medium text-zinc-500 transition group-aria-[current=true]:text-teal-700">{{ $product->category }} · {{ $product->author?->name ?? 'Independent maker' }}</span>
                                    </span>
                                    <x-heroicon-o-arrow-up-right class="relative z-10 size-4 text-zinc-400 transition group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-teal-700 group-aria-[current=true]:text-teal-700" />
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="order-1 lg:order-2">
                        <div class="relative aspect-[16/11] overflow-hidden bg-zinc-100 lg:aspect-auto lg:h-[540px]">
                            @foreach ($exhibitionProducts as $product)
                                <a href="{{ route('products.show', $product) }}" data-exhibition-preview="{{ $loop->index }}" @if (! $loop->first) hidden @endif class="absolute inset-0 block overflow-hidden">
                                    <img src="{{ $product->coverUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                                    <span class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-5 bg-gradient-to-t from-zinc-950/90 via-zinc-950/35 to-transparent px-5 pb-5 pt-28 text-white sm:px-8 sm:pb-8">
                                        <span>
                                            <span class="block text-2xl font-extrabold tracking-[-0.025em] sm:text-3xl">{{ $product->name }}</span>
                                            <span class="mt-2 block text-xs font-bold text-zinc-300">{{ number_format($product->displayRating(), 1) }} rating · {{ number_format((float) $product->price) }} ETB</span>
                                        </span>
                                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-teal-300 text-teal-950 transition-transform hover:rotate-12"><x-heroicon-o-arrow-up-right class="size-5" /></span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>

    @if ($popularProducts->isNotEmpty())
        <section class="bg-white">
            <div class="mx-auto max-w-[1400px] px-5 py-20 lg:px-8 lg:py-24">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-4xl font-extrabold tracking-[-0.04em] text-zinc-950 sm:text-5xl">Popular right now</h2>
                        <p class="mt-3 max-w-xl text-sm leading-6 text-zinc-600">A fast read on what customers are exploring across MerebHub.</p>
                    </div>
                    <a href="{{ route('store.bestsellers') }}" class="group inline-flex items-center gap-2 text-sm font-extrabold text-teal-700">See best sellers <x-heroicon-o-arrow-right class="size-4 transition-transform group-hover:translate-x-1" /></a>
                </div>

                <div class="mt-9 grid border-y border-zinc-200 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($popularProducts as $index => $product)
                        <a wire:key="popular-{{ $product->id }}" href="{{ route('products.show', $product) }}" class="group flex min-w-0 items-center gap-4 border-b border-zinc-200 py-5 md:border-r-0 md:px-5 md:[&:nth-child(odd)]:border-r md:[&:nth-last-child(-n+2)]:border-b-0 xl:border-r-0 xl:[&:nth-child(3n)]:border-r-0 xl:[&:nth-child(3n+1)]:border-r xl:[&:nth-child(3n+2)]:border-r xl:[&:nth-child(3n+1)]:pl-0 xl:[&:nth-child(3n)]:pr-0 xl:[&:nth-last-child(-n+3)]:border-b-0">
                            <span class="w-5 shrink-0 text-xs font-extrabold tabular-nums text-zinc-400">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <img src="{{ $product->coverUrl() }}" alt="" loading="lazy" class="size-16 shrink-0 rounded-xl bg-zinc-100 object-cover transition duration-300 group-hover:scale-[1.04]">
                            <span class="min-w-0 flex-1">
                                <strong class="block truncate text-sm text-zinc-950 transition group-hover:text-teal-700">{{ $product->name }}</strong>
                                <span class="mt-1 block truncate text-xs font-medium text-zinc-500">{{ $product->author?->name ?? 'Independent maker' }} · {{ $product->category }}</span>
                                <span class="mt-2 flex items-center gap-3 text-xs font-bold text-zinc-600"><span class="flex items-center gap-1"><x-heroicon-s-star class="size-3.5 text-amber-400" /> {{ number_format($product->displayRating(), 1) }}</span><span>{{ number_format((float) $product->price) }} ETB</span></span>
                            </span>
                            <x-heroicon-o-arrow-up-right class="size-4 shrink-0 text-zinc-300 transition group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-teal-600" />
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section data-home-local-build class="relative isolate overflow-hidden bg-zinc-950 py-24 text-white sm:py-28 lg:py-32">
        <svg aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 h-full w-full text-white/10" viewBox="0 0 1440 720" preserveAspectRatio="xMidYMid slice" fill="none">
            <path d="M-120 610C238 492 416 681 716 514C1008 351 1114 92 1560 142" stroke="currentColor" stroke-width="1" vector-effect="non-scaling-stroke" />
            <path d="M-80 676C230 548 469 728 760 558C1043 393 1208 224 1518 230" stroke="currentColor" stroke-width="1" vector-effect="non-scaling-stroke" />
            <path d="M720 -80C802 188 1014 263 1520 250" stroke="currentColor" stroke-width="1" vector-effect="non-scaling-stroke" />
            <circle cx="1158" cy="204" r="164" stroke="currentColor" stroke-width="1" vector-effect="non-scaling-stroke" />
            <circle cx="1158" cy="204" r="246" stroke="currentColor" stroke-width="1" vector-effect="non-scaling-stroke" />
        </svg>
        <svg aria-hidden="true" class="pointer-events-none absolute -right-20 top-8 -z-10 size-[32rem] text-teal-300/15" viewBox="0 0 500 500" fill="none">
            <path d="M26 322L177 76L276 245L474 164" stroke="currentColor" stroke-width="1.2" vector-effect="non-scaling-stroke" />
            <path d="M62 402L224 138L320 301L456 245" stroke="currentColor" stroke-width="1.2" vector-effect="non-scaling-stroke" />
        </svg>

        <div class="mx-auto max-w-[1400px] px-5 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-12 lg:items-end">
                <p class="max-w-[14ch] text-[clamp(3.25rem,7vw,5.75rem)] font-extrabold leading-[.88] tracking-[-0.04em] text-balance lg:col-span-7">
                    Built here doesn’t mean built small.
                </p>
                <p class="max-w-lg text-base leading-8 text-zinc-300 lg:col-span-5 lg:justify-self-end">MerebHub gives independent makers a marketplace shaped for Ethiopian customers—and gives customers a clearer way to discover, buy, and return to their software.</p>
            </div>

            <div class="mt-16 grid border-y border-white/15 sm:grid-cols-3">
                <div class="py-6 sm:pr-7"><x-heroicon-o-shield-check class="size-5 text-teal-300" /><strong class="mt-4 block text-sm">Reviewed publishers</strong><p class="mt-2 text-xs leading-6 text-zinc-400">Publisher information stays visible while you compare.</p></div>
                <div class="border-t border-white/15 py-6 sm:border-l sm:border-t-0 sm:px-7"><x-heroicon-o-banknotes class="size-5 text-teal-300" /><strong class="mt-4 block text-sm">ETB checkout</strong><p class="mt-2 text-xs leading-6 text-zinc-400">Pricing and checkout are presented in familiar local currency.</p></div>
                <div class="border-t border-white/15 py-6 sm:border-l sm:border-t-0 sm:pl-7"><x-heroicon-o-key class="size-5 text-teal-300" /><strong class="mt-4 block text-sm">One account</strong><p class="mt-2 text-xs leading-6 text-zinc-400">Purchases, licenses, invoices, and support stay together.</p></div>
            </div>
        </div>
    </section>

    @if ($categories->isNotEmpty())
        <section class="border-b border-zinc-200 bg-white">
            <div class="mx-auto grid max-w-[1400px] gap-12 px-5 py-20 lg:grid-cols-[minmax(0,.72fr)_minmax(0,1.28fr)] lg:px-8 lg:py-24">
                <div>
                    <h2 class="public-section-title max-w-[11ch]">Start with what you need to do.</h2>
                    <p class="mt-5 max-w-md text-sm leading-7 text-zinc-600">Browse by the work in front of you, then compare the products built to handle it.</p>
                    <a href="{{ route('store.index') }}" class="btn-dark mt-7">Explore every category <x-heroicon-o-arrow-right class="size-4" /></a>
                </div>
                <div class="grid border-t border-zinc-200 sm:grid-cols-2">
                    @foreach ($categories->take(8) as $item)
                        <a href="{{ route('store.index', ['category' => $item->name]) }}" class="group flex min-h-24 items-center justify-between gap-5 border-b border-zinc-200 py-5 sm:px-6 sm:odd:border-r sm:even:pr-0 sm:odd:pl-0">
                            <span>
                                <span class="mb-4 block text-teal-700 transition duration-200 group-hover:-translate-y-0.5"><x-dynamic-component :component="$item->iconComponent()" class="size-5" /></span>
                                <strong class="block text-base text-zinc-950 transition group-hover:text-teal-700">{{ $item->name }}</strong>
                                <span class="mt-1 block text-xs font-medium text-zinc-500">Browse software</span>
                            </span>
                            <x-heroicon-o-arrow-up-right class="size-4 shrink-0 text-zinc-400 transition duration-200 group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-teal-700" />
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($newProducts->isNotEmpty())
        <section class="border-b border-zinc-200 bg-zinc-50/50">
            <div class="mx-auto max-w-[1400px] px-5 py-20 lg:px-8 lg:py-28">
                <div class="grid gap-7 lg:grid-cols-12 lg:items-end">
                    <h2 class="text-5xl font-extrabold leading-[.92] tracking-[-0.04em] text-zinc-950 sm:text-6xl lg:col-span-8 lg:text-7xl">New tools.<br>New possibilities.</h2>
                    <div class="lg:col-span-4"><p class="max-w-sm text-sm leading-7 text-zinc-600">The latest software published across the marketplace, arranged for browsing rather than scrolling past.</p><a href="{{ route('store.newarrivals') }}" class="group mt-5 inline-flex items-center gap-2 text-sm font-extrabold text-teal-700">See every new arrival <x-heroicon-o-arrow-right class="size-4 transition-transform group-hover:translate-x-1" /></a></div>
                </div>

                <div class="mt-14 grid gap-x-6 gap-y-12 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($newProducts as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
