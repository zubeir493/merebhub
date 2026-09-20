@props(['product'])

<article {{ $attributes->class(['group min-w-0']) }}>
    <a href="{{ route('products.show', $product) }}" class="block">
        <div class="relative grid aspect-[16/10] place-items-center overflow-hidden rounded-sm bg-zinc-100">
            <span aria-hidden="true" class="text-3xl font-extrabold tracking-[-0.08em] text-zinc-300">{{ str($product->name)->substr(0, 2)->upper() }}</span>
            @if ($product->coverUrl())
                <img
                    src="{{ $product->coverUrl() }}"
                    alt="{{ $product->name }}"
                    class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                    loading="lazy"
                    decoding="async"
                >
            @endif
            @if ($product->compare_at_price)
                <span class="absolute left-3 top-3 rounded-sm bg-white px-2 py-1 text-xs font-bold text-rose-700 ring-1 ring-zinc-950/10">
                    Save {{ round((1 - ($product->price / $product->compare_at_price)) * 100) }}%
                </span>
            @endif
        </div>
    </a>
    <div class="mt-3 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('products.show', $product) }}" class="block truncate text-base font-bold text-zinc-950 transition group-hover:text-teal-700">{{ $product->name }}</a>
            <p class="mt-1 truncate text-xs font-medium text-zinc-500">
                @if ($product->author)
                    <a href="{{ route('vendors.show', $product->author) }}" class="hover:text-teal-700">{{ $product->author->name }}</a>
                @else
                    Independent
                @endif
                · {{ $product->category }}
            </p>
        </div>
        <strong class="shrink-0 text-sm text-zinc-950 tabular-nums">{{ number_format((float) $product->price) }} ETB</strong>
    </div>
    <div class="mt-1.5 flex items-center gap-1 text-xs text-zinc-500">
        <x-heroicon-s-star class="size-3.5 text-amber-400" />
        <span class="font-bold text-zinc-700">{{ number_format($product->displayRating(), 1) }}</span>
        <span>({{ number_format($product->displayRatingsCount()) }})</span>
    </div>
</article>
