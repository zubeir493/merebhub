@props([
    'routeName',
    'search',
    'category',
    'platform',
    'sort',
    'categories',
    'platforms',
])

<form action="{{ route($routeName) }}" method="GET" data-catalog-filter-form {{ $attributes->class(['grid gap-8']) }}>
    @if ($search !== '')
        <input type="hidden" name="q" value="{{ $search }}">
    @endif

    <input type="hidden" name="sort" value="{{ $sort }}">

    <fieldset>
        <legend class="sr-only">Category</legend>
        <div class="flex items-center justify-between gap-4">
            <span class="text-sm font-extrabold text-zinc-950">Category</span>
            @if ($category !== '')
                <span class="text-[11px] font-bold text-teal-700">1 selected</span>
            @endif
        </div>
        <div class="mt-3 grid gap-1.5">
            <label class="group relative cursor-pointer">
                <input type="radio" name="category" value="" class="peer sr-only" @checked($category === '')>
                <span class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-sm font-bold text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950 peer-checked:bg-zinc-950 peer-checked:text-white peer-focus-visible:ring-4 peer-focus-visible:ring-teal-500/10">
                    <x-heroicon-o-squares-2x2 class="size-5" />
                    All categories
                </span>
            </label>
            @foreach ($categories as $item)
                <label class="group relative cursor-pointer">
                    <input type="radio" name="category" value="{{ $item->name }}" class="peer sr-only" @checked($category === $item->name)>
                    <span class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-sm font-bold text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950 peer-checked:bg-zinc-950 peer-checked:text-white peer-focus-visible:ring-4 peer-focus-visible:ring-teal-500/10">
                        <x-dynamic-component :component="$item->iconComponent()" class="size-5" />
                        {{ $item->name }}
                    </span>
                </label>
            @endforeach
        </div>
    </fieldset>

    <fieldset>
        <legend class="text-sm font-extrabold text-zinc-950">Platform</legend>
        <div class="mt-3 flex flex-wrap gap-2">
            <label class="cursor-pointer">
                <input type="radio" name="platform" value="" class="peer sr-only" @checked($platform === '')>
                <span class="inline-flex min-h-9 items-center rounded-lg bg-zinc-100 px-3 text-xs font-bold text-zinc-600 transition hover:bg-zinc-200 hover:text-zinc-950 peer-checked:bg-zinc-950 peer-checked:text-white peer-focus-visible:ring-4 peer-focus-visible:ring-teal-500/10">Any</span>
            </label>
            @foreach ($platforms as $item)
                <label class="cursor-pointer">
                    <input type="radio" name="platform" value="{{ $item->slug }}" class="peer sr-only" @checked($platform === $item->slug)>
                    <span class="inline-flex min-h-9 items-center rounded-lg bg-zinc-100 px-3 text-xs font-bold text-zinc-600 transition hover:bg-zinc-200 hover:text-zinc-950 peer-checked:bg-zinc-950 peer-checked:text-white peer-focus-visible:ring-4 peer-focus-visible:ring-teal-500/10">{{ $item->name }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>

</form>
