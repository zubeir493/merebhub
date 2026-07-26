@props(['product'])

<article {{ $attributes->class(['group min-w-0']) }}>
    <a href="{{ route('products.show', $product) }}" class="block">
        <div class="relative aspect-[16/10] overflow-hidden rounded-lg bg-zinc-100">
            @if ($product->coverUrl())
                <img
                    src="{{ $product->coverUrl() }}"
                    alt="{{ $product->name }}"
                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                    loading="lazy"
                >
            @endif
            @if ($product->compare_at_price)
                <span class="absolute left-3 top-3 rounded-md bg-white px-2 py-1 text-xs font-extrabold text-rose-600 shadow-sm">
                    Save {{ round((1 - ($product->price / $product->compare_at_price)) * 100) }}%
                </span>
            @endif
        </div>
    </a>
    <div class="mt-3 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('products.show', $product) }}" class="block truncate text-[15px] font-extrabold text-zinc-950 group-hover:text-teal-700">{{ $product->name }}</a>
            <p class="mt-1 truncate text-xs font-medium text-zinc-500">
                @if ($product->author->status === \App\Enums\AuthorStatus::Active && $product->author->is_public)
                    <a href="{{ route('vendors.show', $product->author) }}" class="hover:text-teal-700">{{ $product->author->name }}</a>
                @else
                    {{ $product->author->name }}
                @endif
                · {{ $product->category }}
            </p>
        </div>
        <strong class="shrink-0 text-sm text-zinc-950">{{ number_format((float) $product->price) }} ETB</strong>
    </div>
    <div class="mt-1.5 flex items-center gap-1 text-xs text-zinc-500">
        <x-heroicon-s-star class="size-3.5 text-amber-400" />
        <span class="font-bold text-zinc-700">{{ number_format((float) $product->rating, 1) }}</span>
        <span>({{ number_format($product->ratings_count) }})</span>
    </div>
</article>
