@extends('layouts.storefront')

@section('content')
    <article class="mx-auto max-w-3xl px-5 py-16 lg:py-24">
        <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-teal-700">MerebHub</p>
        <h1 class="mt-3 text-4xl font-extrabold tracking-[-0.04em] text-zinc-950">Privacy Policy</h1>
        <p class="mt-4 text-sm text-zinc-500">Last updated {{ now()->toFormattedDateString() }}</p>
        <div class="prose prose-zinc mt-10 max-w-none">
            <p>This policy explains how MerebHub uses information needed to operate the marketplace, process purchases, provide licenses and downloads, and respond to support requests.</p>
            <h2>Information we use</h2>
            <p>We use account details, purchase and license records, support messages, and technical information needed for security, troubleshooting, and reliable delivery.</p>
            <h2>How we use it</h2>
            <p>We use this information to authenticate accounts, fulfill purchases, send service messages, prevent abuse, improve the marketplace, and meet legal obligations. We do not sell personal information.</p>
            <h2>Retention and choices</h2>
            <p>We retain records for as long as needed for these purposes and applicable obligations. You can contact us to ask about your personal information or account.</p>
            <h2>Contact</h2>
            <p>Privacy questions can be sent through our <a href="{{ route('contact.index') }}">contact page</a>.</p>
        </div>
    </article>
@endsection
