@extends('layouts.storefront')

@section('content')
    <section class="relative isolate overflow-hidden bg-zinc-50">
        <x-ambient-lines class="absolute -right-20 -top-28 -z-10 h-[38rem] w-[min(62vw,54rem)] opacity-65" />
        <div class="mx-auto grid max-w-[1400px] gap-8 px-5 py-16 lg:grid-cols-12 lg:items-end lg:px-8 lg:py-24">
            <div class="lg:col-span-8">
                <h1 class="max-w-[10ch] text-[clamp(3.4rem,7vw,6rem)] font-extrabold leading-[.92] tracking-[-0.04em] text-zinc-950 text-balance">Start the right conversation.</h1>
            </div>
            <div class="lg:col-span-4 lg:pb-1">
                <p class="max-w-md text-base font-medium leading-8 text-zinc-600 text-pretty">Questions about MerebHub, a partnership, or a purchase? Point us in the right direction and tell us what you need.</p>
            </div>
        </div>
    </section>

    <section class="mx-auto grid max-w-[1400px] gap-14 px-5 py-16 lg:grid-cols-12 lg:px-8 lg:py-24">
        <div class="lg:col-span-5">
            <h2 class="max-w-sm text-3xl font-extrabold tracking-[-0.03em] text-zinc-950">Before you write</h2>
            <div class="mt-8 border-t border-zinc-200">
                <a href="{{ route('account.support.index') }}" class="group flex items-start justify-between gap-6 border-b border-zinc-200 py-6">
                    <span>
                        <strong class="block text-base text-zinc-950 transition group-hover:text-teal-700">Help with an order or license</strong>
                        <span class="mt-2 block max-w-md text-sm leading-6 text-zinc-600">Signed-in customers can open a private support request and follow every reply.</span>
                    </span>
                    <x-heroicon-o-arrow-up-right class="mt-1 size-5 shrink-0 text-zinc-400 transition group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-teal-700" />
                </a>
                <a href="{{ route('developers.index') }}#apply" class="group flex items-start justify-between gap-6 border-b border-zinc-200 py-6">
                    <span>
                        <strong class="block text-base text-zinc-950 transition group-hover:text-teal-700">Want to sell your software?</strong>
                        <span class="mt-2 block max-w-md text-sm leading-6 text-zinc-600">Use the developer application so the product reaches the right review path.</span>
                    </span>
                    <x-heroicon-o-arrow-up-right class="mt-1 size-5 shrink-0 text-zinc-400 transition group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-teal-700" />
                </a>
                <div class="flex items-start gap-4 border-b border-zinc-200 py-6">
                    <x-heroicon-o-chat-bubble-left-right class="mt-0.5 size-5 shrink-0 text-teal-700" />
                    <p class="max-w-md text-sm leading-6 text-zinc-600">For marketplace questions, partnerships, feedback, or anything that does not fit those paths, use the form.</p>
                </div>
            </div>
        </div>

        <div id="contact-form" class="scroll-mt-24 lg:col-span-7 lg:pl-8">
            <form method="POST" action="{{ route('contact.store') }}" class="grid gap-6 rounded-xl bg-zinc-50 p-6 sm:p-8">
                @csrf
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="contact-name" class="form-label">Your name</label>
                        <input id="contact-name" name="name" value="{{ old('name', auth()->user()?->name) }}" autocomplete="name" maxlength="120" class="form-input" required>
                        @error('name')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="contact-email" class="form-label">Email</label>
                        <input id="contact-email" name="email" type="email" value="{{ old('email', auth()->user()?->email) }}" autocomplete="email" maxlength="255" class="form-input" required>
                        @error('email')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label for="contact-topic" class="form-label">What is this about?</label>
                    <select id="contact-topic" name="topic" class="form-input" required>
                        <option value="">Choose a topic</option>
                        <option value="marketplace" @selected(old('topic') === 'marketplace')>Marketplace question</option>
                        <option value="partnership" @selected(old('topic') === 'partnership')>Partnership</option>
                        <option value="purchase" @selected(old('topic') === 'purchase')>Purchase question</option>
                        <option value="other" @selected(old('topic') === 'other')>Something else</option>
                    </select>
                    @error('topic')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="contact-subject" class="form-label">Subject</label>
                    <input id="contact-subject" name="subject" value="{{ old('subject') }}" maxlength="160" class="form-input" required>
                    @error('subject')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="contact-message" class="form-label">Message</label>
                    <textarea id="contact-message" name="message" rows="7" maxlength="5000" class="form-input" required>{{ old('message') }}</textarea>
                    @error('message')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="max-w-sm text-xs leading-5 text-zinc-500">We’ll use your email only to respond to this message.</p>
                    <button type="submit" class="btn-dark shrink-0">Send message <x-heroicon-o-paper-airplane class="size-4" /></button>
                </div>
            </form>
        </div>
    </section>
@endsection
