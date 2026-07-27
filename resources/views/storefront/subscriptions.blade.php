@extends('storefront.account.layout')

@section('account-content')
    <header class="mb-8">
        <h1 class="text-4xl font-extrabold tracking-tight">Subscriptions</h1>
        <p class="mt-2 text-zinc-600">Manage your active and expired subscriptions.</p>
    </header>

    <div class="space-y-5">
        @forelse ($subscriptions as $subscription)
            <article class="rounded-2xl border border-zinc-200 bg-white p-5 sm:flex sm:items-center sm:justify-between sm:gap-5 shadow-sm">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold text-zinc-900">{{ $subscription->product->name }} — {{ $subscription->productPlan->name }}</h2>
                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-bold">{{ $subscription->status->getLabel() }}</span>
                    </div>
                    <p class="mt-2 text-sm text-zinc-500">Term: {{ $subscription->starts_at->toFormattedDateString() }} to {{ $subscription->expires_at->toFormattedDateString() }}</p>
                </div>
                @if (in_array($subscription->status, [\App\Enums\SubscriptionStatus::Active, \App\Enums\SubscriptionStatus::Expired], true))
                    <form method="POST" action="{{ route('account.subscriptions.renew', $subscription) }}" class="mt-4 flex sm:mt-0">
                        @csrf
                        <button class="btn-primary w-full sm:w-auto">
                            <x-heroicon-o-arrow-path-rounded-square class="size-4" aria-hidden="true" /> Renew with Chapa
                        </button>
                    </form>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 py-16 text-center">
                <x-heroicon-o-arrow-path-rounded-square class="mx-auto size-12 text-zinc-400" aria-hidden="true" />
                <h2 class="mt-4 text-lg font-semibold">No subscriptions yet</h2>
                <p class="mt-2 text-sm text-zinc-600">Start a subscription to get access to premium software.</p>
            </div>
        @endforelse
    </div>
@endsection
