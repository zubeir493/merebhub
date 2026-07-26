@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-[1400px] px-5 py-12 lg:px-8">
        <p class="text-sm font-extrabold uppercase text-teal-700">Saved for later</p>
        <h1 class="mt-2 border-b border-zinc-200 pb-6 text-4xl font-extrabold">Wishlist</h1>

        @guest
            <section class="py-20 text-center">
                <span class="mx-auto grid size-14 place-items-center rounded-full bg-zinc-100"><x-heroicon-o-heart class="size-7 text-zinc-500" /></span>
                <h2 class="mt-5 text-xl font-extrabold">Log in to create a wishlist</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-zinc-500">Save apps you are considering and return to them from any device.</p>
                <a href="{{ route('login') }}" class="btn-dark mt-6">Log in</a>
            </section>
        @else
            @if ($items->isEmpty())
                <section class="py-20 text-center">
                    <span class="mx-auto grid size-14 place-items-center rounded-full bg-zinc-100"><x-heroicon-o-heart class="size-7 text-zinc-500" /></span>
                    <h2 class="mt-5 text-xl font-extrabold">Nothing saved yet</h2>
                    <a href="{{ route('home') }}#catalog" class="btn-primary mt-6">Explore software</a>
                </section>
            @else
                <div class="mt-8 grid gap-x-5 gap-y-10 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($items as $item)
                        <article>
                            <x-product-card :product="$item->product" />
                            <div class="mt-4 flex gap-2">
                                <form method="POST" action="{{ route('cart.store', $item->product) }}" class="flex-1">
                                    @csrf
                                    <button class="btn-primary w-full"><x-heroicon-o-shopping-cart class="size-4" /> Add to cart</button>
                                </form>
                                <form method="POST" action="{{ route('wishlist.destroy', $item) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="grid size-11 place-items-center rounded-lg border border-zinc-300 hover:bg-zinc-50" aria-label="Remove from wishlist"><x-heroicon-o-trash class="size-4" /></button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        @endguest
    </div>
@endsection
