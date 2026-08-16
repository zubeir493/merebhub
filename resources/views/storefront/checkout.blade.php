@extends('layouts.storefront')

@section('content')
    @php($name = Str::of($user->name)->squish())
    <div class="mx-auto grid max-w-5xl gap-10 px-5 py-14 lg:grid-cols-[1fr_380px] lg:px-8">
        <section>
            <p class="text-sm font-extrabold uppercase text-teal-700">Checkout</p>
            <h1 class="mt-2 text-4xl font-extrabold">Billing details</h1>
            <p class="mt-4 max-w-xl leading-7 text-zinc-600">Your digital order is processed by Lunar and recorded in your MerebHub account.</p>
            <form method="POST" action="{{ route('checkout.store') }}" class="mt-8 grid gap-5 sm:grid-cols-2">
                @csrf
                <input type="hidden" name="country_id" value="{{ $country->id }}">
                <div>
                    <label class="form-label" for="first-name">First name</label>
                    <input id="first-name" name="first_name" value="{{ old('first_name', $name->beforeLast(' ')->toString() ?: $name) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label" for="last-name">Last name</label>
                    <input id="last-name" name="last_name" value="{{ old('last_name', $name->contains(' ') ? $name->afterLast(' ') : '') }}" class="form-input">
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label" for="contact-email">Email</label>
                    <input id="contact-email" name="contact_email" type="email" value="{{ old('contact_email', $user->email) }}" class="form-input" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label" for="line-one">Billing address</label>
                    <input id="line-one" name="line_one" value="{{ old('line_one') }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label" for="city">City</label>
                    <input id="city" name="city" value="{{ old('city', 'Addis Ababa') }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label" for="postcode">Postal code</label>
                    <input id="postcode" name="postcode" value="{{ old('postcode', '1000') }}" class="form-input" required>
                </div>
                <button class="btn-dark sm:col-span-2">Place order <x-heroicon-o-arrow-right class="size-4" /></button>
            </form>
        </section>
        <aside class="h-fit rounded-lg border border-zinc-200 bg-zinc-50 p-5">
            <h2 class="text-lg font-extrabold">Order summary</h2>
            <div class="mt-4 grid gap-4">
                @foreach ($cart->lines as $line)
                    <div class="flex items-center justify-between gap-4 text-sm">
                        <span>{{ $line->purchasable->getDescription() }} × {{ $line->quantity }}</span>
                        <strong>{{ $line->total->format() }}</strong>
                    </div>
                @endforeach
            </div>
            <div class="mt-5 flex justify-between border-t border-zinc-200 pt-4 text-sm"><span>Total</span><strong>{{ $cart->total->format() }}</strong></div>
        </aside>
    </div>
@endsection
