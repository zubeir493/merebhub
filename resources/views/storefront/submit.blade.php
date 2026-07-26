@extends('layouts.storefront')

@section('content')
    <div class="mx-auto grid max-w-6xl gap-12 px-5 py-14 lg:grid-cols-[.72fr_1.28fr] lg:px-8">
        <aside>
            <p class="text-sm font-extrabold uppercase text-teal-700">For Ethiopian makers</p>
            <h1 class="mt-2 text-4xl font-extrabold leading-tight">Tell us about your software.</h1>
            <p class="mt-5 leading-7 text-zinc-600">This is a short introduction, not a final product listing. Share enough for our review team to understand the product and contact you.</p>
            <div class="mt-8 grid gap-5 text-sm">
                <div class="flex gap-3"><x-heroicon-o-chat-bubble-left-right class="size-6 shrink-0 text-teal-600" /><span><strong class="block">Start the conversation</strong><span class="text-zinc-500">We will collect final listing details after review.</span></span></div>
                <div class="flex gap-3"><x-heroicon-o-paper-clip class="size-6 shrink-0 text-teal-600" /><span><strong class="block">Files are optional</strong><span class="text-zinc-500">Screenshots, a PDF, or a ZIP can help us evaluate the app.</span></span></div>
                <div class="flex gap-3"><x-heroicon-o-credit-card class="size-6 shrink-0 text-teal-600" /><span><strong class="block">Flexible pricing</strong><span class="text-zinc-500">One-time, free, and manually renewed plans are supported.</span></span></div>
            </div>
        </aside>
        <section class="rounded-lg border border-zinc-200 bg-zinc-50 p-6 sm:p-8" x-data="{ paymentModel: '{{ old('payment_model', 'one_time') }}' }">
            <h2 class="text-2xl font-extrabold">Submit your app</h2>
            <form method="POST" action="{{ route('submissions.store') }}" enctype="multipart/form-data" class="mt-7 grid gap-5 sm:grid-cols-2">
                @csrf
                <div><label class="form-label">Your name</label><input name="submitter_name" value="{{ old('submitter_name') }}" class="form-input" required>@error('submitter_name')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Email</label><input name="submitter_email" type="email" value="{{ old('submitter_email') }}" class="form-input" required>@error('submitter_email')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">App name</label><input name="app_name" value="{{ old('app_name') }}" class="form-input" required>@error('app_name')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Category</label><input name="category" value="{{ old('category') }}" placeholder="Business, design, games..." class="form-input">@error('category')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Platform</label><select name="platform" class="form-input" required><option value="">Choose platform</option>@foreach (['Web', 'Windows', 'macOS', 'Linux', 'Android', 'iOS', 'Cross-platform'] as $platform)<option value="{{ $platform }}" @selected(old('platform') === $platform)>{{ $platform }}</option>@endforeach</select>@error('platform')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Delivery</label><select name="fulfillment_type" class="form-input" required>@foreach ($fulfillmentTypes as $type)<option value="{{ $type->value }}" @selected(old('fulfillment_type', 'license_key') === $type->value)>{{ $type->label() }}</option>@endforeach</select>@error('fulfillment_type')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Payment model</label><select name="payment_model" x-model="paymentModel" class="form-input" required>@foreach ($billingModels as $model)<option value="{{ $model->value }}">{{ $model->label() }}</option>@endforeach</select>@error('payment_model')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div x-show="paymentModel === 'manual_subscription'" x-cloak><label class="form-label">Billing period</label><select name="billing_interval" class="form-input"><option value="">Choose period</option>@foreach ($billingIntervals as $interval)<option value="{{ $interval->value }}" @selected(old('billing_interval') === $interval->value)>{{ $interval->label() }}</option>@endforeach</select>@error('billing_interval')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Suggested price (ETB)</label><input name="suggested_price" type="number" step="0.01" min="0" value="{{ old('suggested_price') }}" class="form-input">@error('suggested_price')<p class="form-error">{{ $message }}</p>@enderror</div>
                {{-- <div><label class="form-label">Trial length (days)</label><input name="trial_days" type="number" min="1" max="365" value="{{ old('trial_days') }}" placeholder="Optional" class="form-input">@error('trial_days')<p class="form-error">{{ $message }}</p>@enderror</div> --}}
                <div class="sm:col-span-2"><label class="form-label">Website or demo link</label><input name="demo_url" type="url" value="{{ old('demo_url') }}" placeholder="https://" class="form-input">@error('demo_url')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="form-label">Description</label><textarea name="description" rows="6" class="form-input" minlength="50" required>{{ old('description') }}</textarea><p class="mt-2 text-xs text-zinc-500">What does it do, who is it for, and what makes it useful?</p>@error('description')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="form-label">Supporting files <span class="font-medium text-zinc-400">(optional)</span></label><input name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.zip" class="form-input file:mr-4 file:border-0 file:bg-transparent file:text-sm file:font-bold"><p class="mt-2 text-xs text-zinc-500">Up to 8 images, PDFs, or ZIP files. Maximum 20 MB each.</p>@error('attachments')<p class="form-error">{{ $message }}</p>@enderror @error('attachments.*')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><button class="btn-primary">Send for review <x-heroicon-o-arrow-up-tray class="size-4" /></button></div>
            </form>
        </section>
    </div>
@endsection
