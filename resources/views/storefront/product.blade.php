@extends('layouts.storefront')

@section('content')
    @php
        $publishedReviewCount = $product->publishedReviews->count();
        $displayReviewCount = $publishedReviewCount > 0 ? $publishedReviewCount : $product->ratings_count;
        $displayRating = $publishedReviewCount > 0 ? (float) $product->publishedReviews->avg('rating') : $product->rating;
        $reviewsTabActive = $errors->hasAny(['rating', 'title', 'body', 'review']);
    @endphp

    <div class="mx-auto max-w-[1400px] px-5 py-10 lg:px-8">
        <nav class="mb-7 flex items-center gap-2 text-xs font-semibold text-zinc-500">
            <a href="{{ route('home') }}" class="hover:text-teal-700">Discover</a>
            <x-heroicon-o-chevron-right class="size-3.5" />
            <span>{{ $product->category }}</span>
        </nav>
        <section class="grid gap-9 lg:grid-cols-[minmax(0,1.25fr)_minmax(340px,.75fr)]">
            <div data-product-gallery tabindex="0" aria-label="{{ $product->name }} product gallery" class="min-w-0 outline-none">
                <div class="relative aspect-[16/10] overflow-hidden rounded-2xl bg-zinc-100 ring-1 ring-zinc-200">
                    @forelse ($galleryMedia as $media)
                        <div data-gallery-slide @if (! $loop->first) hidden @endif aria-hidden="{{ $loop->first ? 'false' : 'true' }}" class="absolute inset-0">
                            <img src="{{ $media->getUrl() }}" alt="{{ $product->name }} screenshot {{ $loop->iteration }}" class="h-full w-full object-cover">
                        </div>
                    @empty
                        @if ($product->coverUrl())
                            <div data-gallery-slide class="absolute inset-0">
                                <img src="{{ $product->coverUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                            </div>
                        @else
                            <div data-gallery-slide class="grid h-full place-items-center px-8 text-center text-sm font-bold text-zinc-500">
                                Product preview coming soon
                            </div>
                        @endif
                    @endforelse

                    @if ($galleryMedia->count() > 1)
                        <button type="button" data-gallery-prev class="absolute left-4 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-zinc-950/70 text-white shadow-lg transition hover:bg-zinc-950 focus:outline-none focus:ring-4 focus:ring-white/60" aria-label="Previous product image">
                            <x-heroicon-o-chevron-left class="size-5" />
                        </button>
                        <button type="button" data-gallery-next class="absolute right-4 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-zinc-950/70 text-white shadow-lg transition hover:bg-zinc-950 focus:outline-none focus:ring-4 focus:ring-white/60" aria-label="Next product image">
                            <x-heroicon-o-chevron-right class="size-5" />
                        </button>
                        <span data-gallery-counter class="absolute bottom-4 right-4 rounded-full bg-zinc-950/75 px-3 py-1 text-xs font-extrabold text-white" aria-live="polite">1 / {{ $galleryMedia->count() }}</span>
                    @endif
                </div>

                @if ($galleryMedia->count() > 1)
                    <div class="mt-3 flex gap-2 overflow-x-auto pb-1" role="group" aria-label="Product gallery thumbnails">
                        @foreach ($galleryMedia as $media)
                            <button type="button" data-gallery-thumb aria-pressed="{{ $loop->first ? 'true' : 'false' }}" class="h-16 shrink-0 overflow-hidden rounded-lg border-2 {{ $loop->first ? 'border-teal-500 ring-2 ring-teal-500/20' : 'border-transparent' }} bg-zinc-100 transition hover:border-teal-400 focus:outline-none focus:ring-4 focus:ring-teal-500/15" aria-label="Show product image {{ $loop->iteration }}">
                                <img src="{{ $media->getUrl() }}" alt="" class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="flex flex-col">
                <div class="flex justify-between items-start gap-3">
                    <h1 class="mt-3 text-4xl font-extrabold leading-tight text-zinc-950">{{ $product->name }}</h1>
                    @if ($wishlistItem)
                        <form method="POST" action="{{ route('wishlist.destroy', $wishlistItem) }}" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Remove from wishlist">
                                <x-heroicon-s-bookmark class="size-6 text-amber-500" />
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('wishlist.store', $product) }}" class="mt-3">
                            @csrf
                            <button type="submit" title="Save to wishlist">
                                <x-heroicon-o-bookmark class="size-6 text-amber-500" />
                            </button>
                        </form>
                    @endif
                </div>
                <div class="flex justify-between items-start gap-3">
                    @if ($product->author)
                        <a href="{{ route('vendors.show', $product->author) }}"
                            class="mt-3 inline-flex items-center gap-2 text-sm font-extrabold text-teal-700">
                            By {{ $product->author->name }}
                            @if ($product->author->is_verified)<x-heroicon-o-check-badge class="size-4" />@endif
                        </a>
                    @endif
                    <button type="button" data-product-tab-trigger="reviews" class="mt-5 flex items-center gap-2 text-left text-sm transition hover:text-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-500/10">
                        <x-heroicon-s-star class="size-5 text-amber-400" />
                        <strong>{{ number_format($displayRating, 1) }}</strong>
                        <span class="text-zinc-500">{{ number_format($displayReviewCount) }} ratings</span>
                    </button>
                </div>
                <div class="py-6">
                    @if ($product->variants->isNotEmpty())
                        <form method="POST" action="{{ route('cart.store', $product) }}" data-add-to-cart>
                            @csrf
                            <fieldset>
                                <legend class="form-label">Choose an option</legend>
                                <div class="grid gap-3">
                                @foreach ($product->variants as $variant)
                                    @php
                                        $optionLabel = $product->variantDisplayName($variant);
                                        $presentationImage = $product->variantPresentationImage($variant);
                                    @endphp
                                    <label class="group relative block cursor-pointer">
                                        <input type="radio" name="variant_id" value="{{ $variant->id }}" class="peer sr-only" required @checked($loop->first)>
                                        <span class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-3 transition duration-150 hover:border-zinc-400 hover:shadow-sm peer-checked:border-teal-500 peer-checked:bg-teal-50/50 peer-checked:ring-2 peer-checked:ring-teal-500/15">
                                            <span class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-lg bg-zinc-100 text-teal-700 transition group-hover:bg-teal-50">
                                                @if ($presentationImage)
                                                    <img src="{{ $presentationImage }}" alt="" class="h-full w-full object-cover">
                                                @else
                                                    <x-dynamic-component :component="$product->variantPresentationIcon($variant)" class="size-6" />
                                                @endif
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-extrabold text-zinc-900">{{ $optionLabel }}</span>
                                                <span class="mt-1 block text-xs font-semibold text-zinc-500">Digital license</span>
                                            </span>
                                            <strong class="shrink-0 text-sm font-extrabold text-zinc-950">{{ number_format((float) ($variant->prices->first()?->price ?? 0) / 100, 2) }} ETB</strong>
                                        </span>
                                    </label>
                                @endforeach
                                </div>
                            </fieldset>
                            <button type="submit" class="btn-primary mt-4 w-full"><x-heroicon-o-shopping-cart class="size-5" /> Add to
                                cart</button>
                        </form>
                    @else
                        <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">This product is not
                            currently available for purchase.</div>
                    @endif
                    {{-- @auth
                        @if ($wishlistItem)
                            <form method="POST" action="{{ route('wishlist.destroy', $wishlistItem) }}" class="mt-3">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg border border-zinc-300 px-4 py-3 text-sm font-bold text-zinc-700 transition hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700">
                                    <x-heroicon-s-heart class="size-5 text-rose-500" /> Remove from wishlist
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('wishlist.store', $product) }}" class="mt-3">
                                @csrf
                                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg border border-zinc-300 px-4 py-3 text-sm font-bold text-zinc-700 transition hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700">
                                    <x-heroicon-o-heart class="size-5 text-rose-500" /> Save to wishlist
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-zinc-300 px-4 py-3 text-sm font-bold text-zinc-700 transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700">
                            <x-heroicon-o-heart class="size-5 text-rose-500" /> Sign in to save to wishlist
                        </a>
                    @endauth --}}
                    <p class="mt-3 flex items-center justify-center gap-2 text-xs font-semibold text-zinc-500">
                        <x-heroicon-o-shield-check class="size-4" /> Secure checkout powered by chapa.co
                    </p>
                </div>
            </div>
        </section>
        <section id="product-details" data-product-tabs class="mt-12 border-t border-zinc-200 pt-8">
            <div class="flex gap-6 overflow-x-auto border-b border-zinc-200" role="tablist" aria-label="Product details">
                <button type="button" data-product-tab="description" role="tab" aria-selected="{{ $reviewsTabActive ? 'false' : 'true' }}" aria-controls="product-description" class="-mb-px shrink-0 border-b-2 {{ $reviewsTabActive ? 'border-transparent text-zinc-500' : 'border-teal-500 text-zinc-950' }} px-1 pb-4 text-sm font-extrabold transition focus:outline-none focus:ring-4 focus:ring-teal-500/10">Description</button>
                <button type="button" data-product-tab="reviews" role="tab" aria-selected="{{ $reviewsTabActive ? 'true' : 'false' }}" aria-controls="product-reviews" class="-mb-px shrink-0 border-b-2 {{ $reviewsTabActive ? 'border-teal-500 text-zinc-950' : 'border-transparent text-zinc-500' }} px-1 pb-4 text-sm font-extrabold transition hover:text-zinc-950 focus:outline-none focus:ring-4 focus:ring-teal-500/10">Reviews ({{ number_format($displayReviewCount) }})</button>
            </div>

            <div id="product-description" data-product-panel="description" role="tabpanel" tabindex="0" @if ($reviewsTabActive) hidden @endif class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_300px]">
                <div>
                    <h2 class="text-2xl font-extrabold">About this software</h2>
                    <div class="mt-5 max-w-3xl whitespace-pre-line text-base leading-8 text-zinc-600">{{ $product->description }}</div>
                </div>
                <aside class="border-l border-zinc-200 pl-7 text-sm">
                    <h3 class="font-extrabold">Why MerebHub?</h3>
                    <ul class="mt-4 grid gap-3 text-zinc-600">
                        <li class="flex gap-2"><x-heroicon-o-check class="size-5 shrink-0 text-teal-600" /> Secure Payments</li>
                        <li class="flex gap-2"><x-heroicon-o-check class="size-5 shrink-0 text-teal-600" /> Currency-aware pricing</li>
                        <li class="flex gap-2"><x-heroicon-o-check class="size-5 shrink-0 text-teal-600" /> Trusted publishers</li>
                    </ul>
                </aside>
            </div>

            <div id="product-reviews" data-product-panel="reviews" role="tabpanel" tabindex="0" @if (! $reviewsTabActive) hidden @endif class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_340px]">
                <div>
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-extrabold">Customer reviews</h2>
                            <p class="mt-2 text-sm text-zinc-500">See what customers think about {{ $product->name }}.</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <strong class="block text-2xl font-extrabold text-zinc-950">{{ number_format($displayRating, 1) }}</strong>
                            <span class="text-xs font-semibold text-zinc-500">{{ number_format($displayReviewCount) }} ratings</span>
                        </div>
                    </div>

                    <div class="mt-7 divide-y divide-zinc-200">
                        @forelse ($product->publishedReviews as $review)
                            <article class="py-6 first:pt-0">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                    <strong class="text-zinc-900">{{ $review->user?->name ?? 'MerebHub customer' }}</strong>
                                    <span class="text-amber-500" aria-label="{{ $review->rating }} out of 5 stars">{{ str_repeat('★', $review->rating) }}<span class="text-zinc-200">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                                    <time datetime="{{ $review->created_at->toDateString() }}" class="text-xs font-semibold text-zinc-500">{{ $review->created_at->diffForHumans() }}</time>
                                </div>
                                @if ($review->title)
                                    <h3 class="mt-3 font-extrabold text-zinc-950">{{ $review->title }}</h3>
                                @endif
                                <p class="mt-2 whitespace-pre-line text-sm leading-7 text-zinc-600">{{ $review->body }}</p>
                            </article>
                        @empty
                            <div class="rounded-xl bg-zinc-50 px-5 py-6 text-sm text-zinc-600">No written reviews yet. Be the first to share your experience.</div>
                        @endforelse
                    </div>
                </div>

                <div class="self-start rounded-2xl border border-zinc-200 bg-zinc-50/70 p-5">
                    @auth
                        @if ($canReview)
                            <h2 class="text-lg font-extrabold">Write a review</h2>
                            <p class="mt-1 text-sm leading-6 text-zinc-500">Your review helps other customers choose with confidence.</p>
                            <form method="POST" action="{{ route('products.reviews.store', $product) }}" class="mt-5 grid gap-4">
                                @csrf
                                <fieldset>
                                    <legend class="form-label">Your rating</legend>
                                    <div class="flex gap-1" role="radiogroup" aria-label="Choose a rating">
                                        @for ($rating = 1; $rating <= 5; $rating++)
                                            <label class="group cursor-pointer">
                                                <input type="radio" name="rating" value="{{ $rating }}" class="peer sr-only" @checked((int) old('rating', 5) === $rating)>
                                                <span class="grid size-9 place-items-center rounded-lg text-lg text-zinc-300 transition group-hover:bg-amber-50 group-hover:text-amber-400 peer-checked:bg-amber-50 peer-checked:text-amber-500 peer-focus-visible:ring-4 peer-focus-visible:ring-teal-500/15" aria-hidden="true">★</span>
                                                <span class="sr-only">{{ $rating }} {{ $rating === 1 ? 'star' : 'stars' }}</span>
                                            </label>
                                        @endfor
                                    </div>
                                </fieldset>
                                <div>
                                    <label for="review-title" class="form-label">Title <span class="font-medium text-zinc-400">(optional)</span></label>
                                    <input id="review-title" name="title" value="{{ old('title') }}" maxlength="120" class="form-input" placeholder="Summarize your experience">
                                    @error('title')<p class="form-error">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="review-body" class="form-label">Review</label>
                                    <textarea id="review-body" name="body" rows="5" minlength="20" maxlength="2000" required class="form-input" placeholder="What did you like about this product?">{{ old('body') }}</textarea>
                                    @error('body')<p class="form-error">{{ $message }}</p>@enderror
                                </div>
                                @error('rating')<p class="form-error">{{ $message }}</p>@enderror
                                <button type="submit" class="btn-primary w-full">Publish review</button>
                            </form>
                        @else
                            <h2 class="text-lg font-extrabold">Purchase required to review</h2>
                            <p class="mt-2 text-sm leading-6 text-zinc-500">Purchase this product with this account before sharing your experience.</p>
                            @error('review')<p class="form-error mt-4">{{ $message }}</p>@enderror
                        @endif
                    @else
                        <h2 class="text-lg font-extrabold">Have you used {{ $product->name }}?</h2>
                        <p class="mt-2 text-sm leading-6 text-zinc-500">Sign in to share your experience with the MerebHub community.</p>
                        <a href="{{ route('login') }}" class="btn-primary mt-5 w-full">Sign in to write a review</a>
                    @endauth
                </div>
            </div>
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
