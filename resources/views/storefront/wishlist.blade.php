@extends('storefront.account.layout')

@section('account-content')
    <header class="mb-8">
        <h1 class="mt-2 text-4xl font-extrabold tracking-tight">Wishlist</h1>
        <p class="mt-2 text-zinc-600">Items you've saved for later. Return to them from any device.</p>
    </header>

    @guest
        <section class="my-12 rounded-2xl border border-zinc-200 bg-zinc-50 py-12 text-center shadow-sm">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-white"><x-heroicon-o-heart class="size-8 text-zinc-400" /></span>
            <h2 class="mt-6 text-xl font-semibold">Log in to create a wishlist</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-zinc-600">Save apps you are considering and return to them from any device.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('login') }}" class="btn-primary">Log in</a>
                <a href="{{ route('store.index') }}" class="btn-dark">Explore store</a>
            </div>
        </section>
    @else
        @if ($items->isEmpty())
            <section class="my-12 rounded-2xl border border-zinc-200 bg-zinc-50 py-20 text-center shadow-sm">
                <span class="mx-auto grid size-16 place-items-center rounded-full bg-white"><x-heroicon-o-heart class="size-8 text-zinc-400" /></span>
                <h2 class="mt-6 text-xl font-semibold">Nothing saved yet</h2>
                <p class="mt-2 text-sm text-zinc-600">Start exploring and save your favorite software.</p>
                <a href="{{ route('store.index') }}" class="mt-8 inline-block btn-primary">Explore store</a>
            </section>
        @else
            <div class="mt-8 grid gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($items as $item)
                    <article class="group">
                        <div class="rounded-2xl border border-zinc-200 bg-white p-4 transition-all hover:border-teal-200 hover:shadow-md">
                            <x-product-card :product="$item->product" />
                        </div>
                        <div class="mt-3 flex gap-2">
                            @if ($plan = $item->product->activePlans->first())
                                <form method="POST" action="{{ route('cart.store', $item->product) }}" class="flex-1">
                                    @csrf
                                    <input type="hidden" name="product_plan_id" value="{{ $plan->id }}">
                                    <button class="btn-primary w-full text-sm font-medium transition-all group-hover:bg-teal-400">
                                        <x-heroicon-o-shopping-cart class="size-4" /> Add to cart
                                    </button>
                                </form>
                            @else
                                <span class="flex h-11 flex-1 items-center justify-center rounded-lg bg-zinc-100 px-4 text-sm font-semibold text-zinc-500">Unavailable</span>
                            @endif
                            <form method="POST" action="{{ route('wishlist.destroy', $item) }}">
                                @csrf
                                @method('DELETE')
                                <button class="grid size-11 place-items-center rounded-lg border border-zinc-300 bg-white transition-all hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700 active:scale-[0.96]" aria-label="Remove from wishlist">
                                    <x-heroicon-o-trash class="size-4" />
                                </button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    @endguest
@endsection
