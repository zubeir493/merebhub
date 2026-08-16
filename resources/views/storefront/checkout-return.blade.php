@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-2xl px-5 py-20 text-center lg:px-8">
        <span class="mx-auto grid size-16 place-items-center rounded-full bg-teal-50 text-teal-700"><x-heroicon-o-check-circle class="size-9" /></span>
        <h1 class="mt-5 text-3xl font-extrabold">Order confirmed</h1>
        <p class="mt-3 leading-7 text-zinc-600">Your order has been placed successfully and is now available in your account.</p>
        <p class="mt-5 text-sm font-bold text-zinc-500">Order {{ $order->reference }}</p>
        <div class="mt-8 flex justify-center gap-3">
            <a href="{{ route('account.orders') }}" class="btn-primary">View orders</a>
            <a href="{{ route('home') }}" class="btn-dark">Continue shopping</a>
        </div>
    </div>
@endsection
