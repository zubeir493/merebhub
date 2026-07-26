@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-2xl px-5 py-20 text-center lg:px-8">
        @if ($order->status === \App\Enums\OrderStatus::Paid)
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-teal-50 text-teal-700"><x-heroicon-o-check-circle class="size-9" /></span>
            <h1 class="mt-5 text-3xl font-extrabold">Payment confirmed</h1>
            <p class="mt-3 leading-7 text-zinc-600">Your order is paid. Fulfillment is being prepared and will appear in your account.</p>
        @elseif ($order->status === \App\Enums\OrderStatus::PaymentFailed)
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-rose-50 text-rose-700"><x-heroicon-o-x-circle class="size-9" /></span>
            <h1 class="mt-5 text-3xl font-extrabold">Payment was not confirmed</h1>
            <p class="mt-3 leading-7 text-zinc-600">No fulfillment has been issued. You can return to your cart and try again.</p>
        @else
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-amber-50 text-amber-700"><x-heroicon-o-clock class="size-9" /></span>
            <h1 class="mt-5 text-3xl font-extrabold">Payment verification is pending</h1>
            <p class="mt-3 leading-7 text-zinc-600">Chapa is still confirming this payment. This page does not mark an order as paid; verified payment confirmation does.</p>
        @endif

        <p class="mt-5 text-sm font-bold text-zinc-500">Order {{ $order->public_id }}</p>
        <div class="mt-8 flex justify-center gap-3">
            <a href="{{ route('account.orders') }}" class="btn-primary">View orders</a>
            <a href="{{ route('home') }}" class="btn-dark">Continue shopping</a>
        </div>
    </div>
@endsection
