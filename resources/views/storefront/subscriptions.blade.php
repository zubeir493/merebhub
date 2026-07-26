@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-5xl px-5 py-12 lg:px-8">
        <p class="text-sm font-extrabold uppercase text-teal-700">Account</p>
        <h1 class="mt-2 border-b border-zinc-200 pb-6 text-4xl font-extrabold">Subscriptions</h1>

        <div class="mt-8 grid gap-4">
            @forelse ($subscriptions as $subscription)
                <article class="rounded-xl border border-zinc-200 p-5 sm:flex sm:items-center sm:justify-between sm:gap-5">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-extrabold">{{ $subscription->product->name }} — {{ $subscription->productPlan->name }}</h2>
                            <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-bold">{{ $subscription->status->getLabel() }}</span>
                        </div>
                        <p class="mt-2 text-sm text-zinc-500">Term: {{ $subscription->starts_at->toFormattedDateString() }} to {{ $subscription->expires_at->toFormattedDateString() }}</p>
                    </div>
                    @if (in_array($subscription->status, [\App\Enums\SubscriptionStatus::Active, \App\Enums\SubscriptionStatus::Expired], true))
                        <form method="POST" action="{{ route('account.subscriptions.renew', $subscription) }}" class="mt-4 sm:mt-0">
                            @csrf
                            <button class="btn-primary">Renew with Chapa</button>
                        </form>
                    @endif
                </article>
            @empty
                <div class="py-16 text-center">
                    <x-heroicon-o-arrow-path-rounded-square class="mx-auto size-9 text-zinc-400" />
                    <h2 class="mt-4 text-lg font-extrabold">No subscriptions yet</h2>
                </div>
            @endforelse
        </div>
    </div>
@endsection
