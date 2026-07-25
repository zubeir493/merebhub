@extends('layouts.storefront')

@section('content')
    <div class="mx-auto grid max-w-6xl gap-12 px-5 py-14 lg:grid-cols-[.7fr_1.3fr] lg:px-8">
        <aside>
            <p class="text-sm font-extrabold uppercase text-teal-700">For Ethiopian makers</p>
            <h1 class="mt-2 text-4xl font-extrabold leading-tight">Put your software in front of the right buyers.</h1>
            <p class="mt-5 leading-7 text-zinc-600">Send us your build and product details. Every submission is reviewed before publication.</p>
            <div class="mt-8 grid gap-5 text-sm">
                <div class="flex gap-3"><x-heroicon-o-shield-check class="size-6 shrink-0 text-teal-600" /><span><strong class="block">Human review</strong><span class="text-zinc-500">Quality and safety checks before listing.</span></span></div>
                <div class="flex gap-3"><x-heroicon-o-credit-card class="size-6 shrink-0 text-teal-600" /><span><strong class="block">Commerce handled</strong><span class="text-zinc-500">WooCommerce and Chapa power payment.</span></span></div>
                <div class="flex gap-3"><x-heroicon-o-key class="size-6 shrink-0 text-teal-600" /><span><strong class="block">Automatic licensing</strong><span class="text-zinc-500">Keygen delivers licenses after purchase.</span></span></div>
            </div>
        </aside>
        <section class="rounded-lg border border-zinc-200 bg-zinc-50 p-6 sm:p-8">
            <h2 class="text-2xl font-extrabold">Submit your app</h2>
            <form method="post" action="{{ route('submissions.store') }}" enctype="multipart/form-data" class="mt-7 grid gap-5 sm:grid-cols-2">
                @csrf
                <div><label class="form-label">Your name</label><input name="submitter_name" value="{{ old('submitter_name') }}" class="form-input" required>@error('submitter_name')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Email</label><input name="submitter_email" type="email" value="{{ old('submitter_email') }}" class="form-input" required>@error('submitter_email')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">App name</label><input name="app_name" value="{{ old('app_name') }}" class="form-input" required>@error('app_name')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Platform</label><select name="platform" class="form-input" required><option value="">Choose platform</option>@foreach (['Web', 'Windows', 'macOS', 'Linux', 'Android', 'iOS', 'Cross-platform'] as $platform)<option @selected(old('platform') === $platform)>{{ $platform }}</option>@endforeach</select>@error('platform')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Suggested price (ETB)</label><input name="suggested_price" type="number" step="0.01" min="0" value="{{ old('suggested_price') }}" class="form-input">@error('suggested_price')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Build file</label><input name="build" type="file" class="form-input file:mr-4 file:border-0 file:bg-transparent file:text-sm file:font-bold" required>@error('build')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="form-label">Description</label><textarea name="description" rows="7" class="form-input" minlength="50" required>{{ old('description') }}</textarea><p class="mt-2 text-xs text-zinc-500">Explain what it does, who it is for, and what makes it useful.</p>@error('description')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><button class="btn-primary">Send for review <x-heroicon-o-arrow-up-tray class="size-4" /></button></div>
            </form>
        </section>
    </div>
@endsection
