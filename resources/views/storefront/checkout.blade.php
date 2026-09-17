@extends('layouts.storefront')

@section('content')
    <div class=\"mx-auto max-w-2xl px-5 py-20 text-center lg:px-8\">
        <p class=\"text-sm font-extrabold uppercase text-teal-700\">Secure checkout</p>
        <h1 class=\"mt-2 text-4xl font-extrabold tracking-tight\">Redirecting to Chapa</h1>
        <p class=\"mx-auto mt-4 max-w-lg leading-7 text-zinc-600\">Checkout uses the name and email already saved in your MerebHub account. Return to your cart to start payment.</p>
        <a href=\"{{ route('cart.index') }}\" class=\"btn-dark mt-8\">Return to cart</a>
    </div>
@endsection
