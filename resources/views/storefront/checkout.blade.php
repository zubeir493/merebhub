@extends('layouts.storefront')

@section('content')
    <div class="mx-auto grid max-w-5xl gap-10 px-5 py-14 lg:grid-cols-[1fr_380px] lg:px-8">
        <section>
            <p class="text-sm font-extrabold uppercase text-teal-700">Secure checkout</p>
            <h1 class="mt-2 text-4xl font-extrabold">Where should we send your license?</h1>
            <p class="mt-4 max-w-xl leading-7 text-zinc-600">You will continue to WooCommerce and pay with Chapa. MerebHub sends your license and download link as soon as payment clears.</p>
            @if ($errors->has('checkout'))
                <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700">{{ $errors->first('checkout') }}</div>
            @endif
            <form method="post" action="{{ route('checkout.start', $product) }}" class="mt-8">
                @csrf
                <label class="form-label" for="buyer_email">Delivery email</label>
                <input id="buyer_email" name="buyer_email" type="email" value="{{ old('buyer_email', $buyerEmail) }}" class="form-input" required autofocus>
                @error('buyer_email') <p class="form-error">{{ $message }}</p> @enderror
                <button class="btn-dark mt-5 w-full sm:w-auto">Continue to Chapa <x-heroicon-o-arrow-right class="size-4" /></button>
            </form>
        </section>
        <aside class="h-fit rounded-lg border border-zinc-200 bg-zinc-50 p-5">
            <img src="{{ $product->coverUrl() }}" alt="" class="aspect-[16/10] w-full rounded-lg object-cover">
            <h2 class="mt-4 text-lg font-extrabold">{{ $product->name }}</h2>
            <p class="mt-1 text-sm text-zinc-500">by {{ $product->author->name }}</p>
            <div class="mt-5 flex justify-between border-t border-zinc-200 pt-4 text-sm"><span>Total</span><strong>{{ number_format((float) $product->price) }} ETB</strong></div>
        </aside>
    </div>
@endsection
