@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-[1400px] px-5 py-12 lg:px-8">
        <section class="border-b border-zinc-200 pb-10">
            <div class="flex max-w-3xl items-start gap-5">
                @if ($author->avatar_path)
                    <img src="{{ Storage::disk('public')->url($author->avatar_path) }}" alt="{{ $author->name }}" class="size-24 rounded-lg object-cover">
                @else
                    <span class="grid size-24 place-items-center rounded-lg bg-teal-50 text-teal-700"><x-heroicon-o-building-office-2 class="size-10" /></span>
                @endif
                <div>
                    <p class="text-sm font-extrabold uppercase text-teal-700">Verified maker</p>
                    <h1 class="mt-2 text-4xl font-extrabold">{{ $author->name }}</h1>
                    <p class="mt-4 leading-7 text-zinc-600">{{ $author->bio }}</p>
                    @if ($author->website_url)<a href="{{ $author->website_url }}" rel="noopener" target="_blank" class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-teal-700">Visit website <x-heroicon-o-arrow-top-right-on-square class="size-4" /></a>@endif
                </div>
            </div>
        </section>
        <section class="mt-10">
            <h2 class="mb-6 text-2xl font-extrabold">Software by {{ $author->name }}</h2>
            <div class="grid gap-x-5 gap-y-9 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($author->products as $product)<x-product-card :product="$product" />@endforeach
            </div>
        </section>
    </div>
@endsection
