@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-2xl px-5 py-20 text-center lg:px-8">
        <h1 class="text-5xl font-extrabold leading-none tracking-[-0.05em]">Taking you to secure payment</h1>
        <p class="mx-auto mt-5 max-w-lg leading-7 text-zinc-600">We’ll use the name and email saved to your MerebHub account. Return to your cart to review your order and start payment.</p>
        <a href="{{ route('cart.index') }}" class="btn-dark mt-8">Back to your selection</a>
    </div>
@endsection
