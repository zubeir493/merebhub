@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-2xl px-5 py-20 text-center lg:px-8">
        <span class="mx-auto grid size-16 place-items-center rounded-full bg-teal-50 text-teal-700"><x-heroicon-o-check-circle class="size-9" /></span>
        <h1 class="mt-5 text-5xl font-extrabold leading-none tracking-[-0.05em]">Order confirmed</h1>
        <p class="mt-3 leading-7 text-zinc-600">Your payment was verified and your order is now available in your account.</p>
        <p class="mt-5 text-sm font-bold text-zinc-500">Order {{ $order->reference }}</p>
        <div class="mx-auto mt-8 max-w-xl rounded-md border border-indigo-200 bg-indigo-50 px-5 py-4 text-left">
            <div class="flex gap-3">
                <x-heroicon-o-envelope class="mt-0.5 size-5 shrink-0 text-indigo-700" aria-hidden="true" />
                <div>
                    <p class="font-bold text-indigo-950">Your license is being prepared</p>
                    <p class="mt-1 text-sm leading-6 text-indigo-900">We’ll email your Keygen license as soon as fulfillment finishes. It will also appear in Purchases &amp; licenses automatically.</p>
                </div>
            </div>
        </div>
        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row sm:flex-wrap">
            <a href="{{ route('account.purchases') }}" class="btn-primary">View licenses</a>
            <a href="{{ route('account.orders') }}" class="btn-dark">View orders</a>
            <a href="{{ route('home') }}" class="btn-dark">Continue shopping</a>
        </div>
    </div>
@endsection
