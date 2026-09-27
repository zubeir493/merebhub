@extends("layouts.storefront")

@php($title = "Page not found")

@section("content")
    <section class="border-b border-zinc-200 bg-zinc-50">
        <div class="mx-auto flex min-h-[calc(100dvh-4.5rem)] max-w-2xl flex-col items-center justify-center px-5 py-20 text-center lg:px-8">
            <p class="text-8xl font-extrabold leading-none tracking-[-0.08em] sm:text-9xl" aria-hidden="true">404</p>
            <h1 class="mt-6 text-4xl font-extrabold leading-none tracking-[-0.05em] text-zinc-950 text-balance sm:text-5xl">
                This page isn’t here.
            </h1>
            <p class="mt-5 max-w-md text-base font-medium leading-7 text-zinc-600">
                The link may be out of date, or the address may contain a typo.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route("store.index") }}" class="btn-primary">
                    Browse marketplace
                    <x-heroicon-o-arrow-up-right class="size-4" />
                </a>
            </div>
        </div>
    </section>
@endsection
