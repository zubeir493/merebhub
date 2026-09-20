@extends('layouts.storefront')

@section('content')
    <section class="relative isolate overflow-hidden bg-zinc-50">
        <x-ambient-lines class="absolute -right-20 -top-28 -z-10 h-[40rem] w-[min(64vw,56rem)] opacity-70" />
        <div class="mx-auto grid max-w-[1400px] gap-10 px-5 py-16 lg:grid-cols-12 lg:items-end lg:px-8 lg:py-24">
            <div class="lg:col-span-8">
                <h1 class="max-w-[11ch] text-[clamp(3.4rem,7vw,6rem)] font-extrabold leading-[.92] tracking-[-0.04em] text-zinc-950 text-balance">Your software deserves a market.</h1>
            </div>
            <div class="lg:col-span-4 lg:pb-1">
                <p class="max-w-md text-base font-medium leading-8 text-zinc-600 text-pretty">MerebHub brings Ethiopian software into one focused marketplace—so customers can discover it, understand it, and buy it with confidence.</p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="#apply" class="btn-dark group">Start an application <x-heroicon-o-arrow-down class="size-4 transition-transform group-hover:translate-y-0.5" /></a>
                    <a href="{{ route('vendors.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg px-4 text-sm font-extrabold text-teal-700 transition hover:bg-teal-50">Meet current developers <x-heroicon-o-arrow-up-right class="size-4" /></a>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-[1400px] px-5 py-16 lg:px-8 lg:py-24">
        <div class="grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <h2 class="max-w-sm text-4xl font-extrabold leading-[1.02] tracking-[-0.03em] text-zinc-950 text-balance">A direct path from product to shelf.</h2>
                <p class="mt-5 max-w-md text-sm font-medium leading-7 text-zinc-600">We start with the product itself: what it solves, who it is for, and whether customers can use it reliably.</p>
            </div>
            <div class="lg:col-span-8">
                <ol class="border-t border-zinc-200">
                    <li class="grid gap-4 border-b border-zinc-200 py-7 sm:grid-cols-[2rem_minmax(0,.7fr)_minmax(0,1fr)] sm:items-start">
                        <span class="text-sm font-extrabold tabular-nums text-teal-700">01</span>
                        <h3 class="text-xl font-extrabold text-zinc-950">Show us the product</h3>
                        <p class="text-sm leading-7 text-zinc-600">Share what you have built, where it is in its lifecycle, and the customer it serves.</p>
                    </li>
                    <li class="grid gap-4 border-b border-zinc-200 py-7 sm:grid-cols-[2rem_minmax(0,.7fr)_minmax(0,1fr)] sm:items-start">
                        <span class="text-sm font-extrabold tabular-nums text-teal-700">02</span>
                        <h3 class="text-xl font-extrabold text-zinc-950">Review the fit</h3>
                        <p class="text-sm leading-7 text-zinc-600">We look at product readiness, presentation, support expectations, and marketplace fit.</p>
                    </li>
                    <li class="grid gap-4 border-b border-zinc-200 py-7 sm:grid-cols-[2rem_minmax(0,.7fr)_minmax(0,1fr)] sm:items-start">
                        <span class="text-sm font-extrabold tabular-nums text-teal-700">03</span>
                        <h3 class="text-xl font-extrabold text-zinc-950">Prepare the listing</h3>
                        <p class="text-sm leading-7 text-zinc-600">If it is a match, we work through the information needed to publish and sell it clearly.</p>
                    </li>
                </ol>
            </div>
        </div>
    </section>

    <section class="relative isolate overflow-hidden bg-zinc-950 text-white">
        <x-ambient-lines variant="dark" class="absolute -right-20 -top-52 -z-10 h-[46rem] w-[min(70vw,60rem)] opacity-80" />
        <div class="mx-auto grid max-w-[1400px] gap-12 px-5 py-16 lg:grid-cols-12 lg:px-8 lg:py-24">
            <div class="lg:col-span-7">
                <h2 class="max-w-[13ch] text-4xl font-extrabold leading-[.98] tracking-[-0.035em] text-balance sm:text-6xl">You keep building. We make it easier to discover and buy.</h2>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:col-span-5 lg:content-end">
                <div>
                    <x-heroicon-o-cursor-arrow-rays class="size-6 text-teal-300" />
                    <h3 class="mt-4 font-extrabold">A focused storefront</h3>
                    <p class="mt-2 text-sm leading-7 text-zinc-400">A product page that explains the offer without making customers decode it.</p>
                </div>
                <div>
                    <x-heroicon-o-credit-card class="size-6 text-teal-300" />
                    <h3 class="mt-4 font-extrabold">Commerce in one place</h3>
                    <p class="mt-2 text-sm leading-7 text-zinc-400">Pricing, checkout, purchases, and digital fulfillment connected in one experience.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="apply" class="scroll-mt-24 bg-zinc-50">
        <div class="mx-auto grid max-w-[1400px] gap-12 px-5 py-16 lg:grid-cols-12 lg:px-8 lg:py-24">
            <div class="lg:col-span-4">
                <div class="lg:sticky lg:top-28">
                    <h2 class="text-4xl font-extrabold leading-[1.02] tracking-[-0.03em] text-zinc-950">Tell us what you’re building.</h2>
                    <p class="mt-5 max-w-md text-sm leading-7 text-zinc-600">A concise application is enough. Links are useful when the product is already available, but work in progress is welcome too.</p>
                    <p class="mt-8 flex items-start gap-3 text-sm font-bold leading-6 text-zinc-700"><x-heroicon-o-lock-closed class="mt-0.5 size-5 shrink-0 text-teal-700" />Your application is used only to evaluate a potential MerebHub listing.</p>
                </div>
            </div>
            <div class="lg:col-span-8 lg:pl-8">
                <form method="POST" action="{{ route('developers.apply') }}" class="grid gap-6 rounded-xl bg-white p-6 shadow-[0_24px_70px_-40px_rgba(24,24,27,.35)] sm:p-8">
                    @csrf
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label for="developer-name" class="form-label">Your name</label>
                            <input id="developer-name" name="name" value="{{ old('name', auth()->user()?->name) }}" autocomplete="name" maxlength="120" class="form-input" required>
                            @error('name')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="developer-email" class="form-label">Email</label>
                            <input id="developer-email" name="email" type="email" value="{{ old('email', auth()->user()?->email) }}" autocomplete="email" maxlength="255" class="form-input" required>
                            @error('email')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="developer-studio" class="form-label">Studio or company <span class="font-medium text-zinc-500">Optional</span></label>
                            <input id="developer-studio" name="studio" value="{{ old('studio') }}" autocomplete="organization" maxlength="160" class="form-input">
                            @error('studio')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="developer-website" class="form-label">Website <span class="font-medium text-zinc-500">Optional</span></label>
                            <input id="developer-website" name="website_url" type="url" value="{{ old('website_url') }}" inputmode="url" placeholder="https://" maxlength="2048" class="form-input">
                            @error('website_url')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="h-px bg-zinc-100"></div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label for="product-name" class="form-label">Product name</label>
                            <input id="product-name" name="product_name" value="{{ old('product_name') }}" maxlength="160" class="form-input" required>
                            @error('product_name')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="product-stage" class="form-label">Current stage</label>
                            <select id="product-stage" name="product_stage" class="form-input" required>
                                <option value="">Choose a stage</option>
                                <option value="live" @selected(old('product_stage') === 'live')>Live and available</option>
                                <option value="beta" @selected(old('product_stage') === 'beta')>Beta or early access</option>
                                <option value="in_development" @selected(old('product_stage') === 'in_development')>In development</option>
                            </select>
                            @error('product_stage')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="product-url" class="form-label">Product link <span class="font-medium text-zinc-500">Optional</span></label>
                        <input id="product-url" name="product_url" type="url" value="{{ old('product_url') }}" inputmode="url" placeholder="A website, demo, or product page" maxlength="2048" class="form-input">
                        @error('product_url')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="product-summary" class="form-label">What does it do?</label>
                        <textarea id="product-summary" name="summary" rows="5" maxlength="1500" class="form-input" required>{{ old('summary') }}</textarea>
                        @error('summary')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="product-audience" class="form-label">Who is it for? <span class="font-medium text-zinc-500">Optional</span></label>
                        <textarea id="product-audience" name="audience" rows="3" maxlength="500" class="form-input">{{ old('audience') }}</textarea>
                        @error('audience')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="max-w-md text-xs leading-5 text-zinc-500">Submitting this form does not guarantee publication. It gives us what we need to start a review.</p>
                        <button type="submit" class="btn-primary shrink-0">Submit application <x-heroicon-o-arrow-right class="size-4" /></button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
