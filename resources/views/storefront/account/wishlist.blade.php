@extends('storefront.account.layout')

@section('account-content')
    <header class="account-page-header">
        <h1 class="account-page-title">Wishlist</h1>
        <p class="account-page-description">Save software you want to revisit on any device.</p>
    </header>

    @if ($items->isEmpty())
        <section class="account-empty-state">
            <x-heroicon-o-heart class="mx-auto size-12 text-zinc-400" />
            <h2 class="mt-4 text-lg font-semibold">Your wishlist is empty</h2>
            <p class="mt-2 text-sm text-zinc-600">Save products while browsing and they will stay with your account.</p>
            <a href="{{ route('store.index') }}" class="btn-primary mt-6">Browse software</a>
        </section>
    @else
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($items as $item)
                <article class="overflow-hidden border border-zinc-200 bg-white">
                    <a href="{{ route('products.show', $item->product) }}" class="block">
                        <div class="aspect-[16/10] overflow-hidden bg-zinc-100">
                            @if ($item->product->coverUrl())
                                <img src="{{ $item->product->coverUrl() }}" alt="{{ $item->product->name }}" class="h-full w-full object-cover" loading="lazy">
                            @endif
                        </div>
                    </a>
                    <div class="p-5">
                        <a href="{{ route('products.show', $item->product) }}" class="text-lg font-extrabold text-zinc-950 hover:text-teal-700">{{ $item->product->name }}</a>
                        <p class="mt-2 line-clamp-2 text-sm leading-6 text-zinc-600">{{ $item->product->short_description }}</p>
                        <div class="mt-5 flex items-center justify-between gap-3">
                            <strong class="text-sm text-zinc-950">{{ number_format((float) $item->product->price) }} ETB</strong>
                            <form method="POST" action="{{ route('wishlist.destroy', $item) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-bold text-rose-600 hover:text-rose-800">Remove</button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8">{{ $items->links() }}</div>
    @endif
@endsection
