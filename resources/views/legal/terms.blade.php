@extends('layouts.storefront')

@section('content')
    <article class="mx-auto max-w-3xl px-5 py-16 lg:py-24">
        <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-teal-700">MerebHub</p>
        <h1 class="mt-3 text-4xl font-extrabold tracking-[-0.04em] text-zinc-950">Terms of Service</h1>
        <p class="mt-4 text-sm text-zinc-500">Last updated {{ now()->toFormattedDateString() }}</p>
        <div class="prose prose-zinc mt-10 max-w-none">
            <p>These terms govern your use of MerebHub and its marketplace services. By creating an account or using the marketplace, you agree to use the service lawfully and to provide accurate information.</p>
            <h2>Accounts and purchases</h2>
            <p>You are responsible for keeping your account credentials confidential. Digital products, licenses, refunds, and support are subject to the information shown at purchase and any maker-specific terms presented with the product.</p>
            <h2>Acceptable use</h2>
            <p>Do not misuse the service, attempt unauthorized access, interfere with other users, or submit content that violates applicable law or another person’s rights.</p>
            <h2>Changes</h2>
            <p>We may update these terms as the service changes. We will publish the current version on this page.</p>
            <h2>Contact</h2>
            <p>Questions about these terms can be sent through our <a href="{{ route('contact.index') }}">contact page</a>.</p>
        </div>
    </article>
@endsection
