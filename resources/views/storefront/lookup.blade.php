@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-xl px-5 py-16">
        <div class="text-center">
            <span class="mx-auto grid size-12 place-items-center rounded-lg bg-teal-50 text-teal-700"><x-heroicon-o-magnifying-glass class="size-6" /></span>
            <h1 class="mt-5 text-3xl font-extrabold">Look up your order</h1>
            <p class="mt-3 text-sm leading-6 text-zinc-600">Use the email from checkout and your WooCommerce order reference.</p>
        </div>
        <form method="post" action="{{ route('orders.lookup.result') }}" class="mt-8 rounded-lg border border-zinc-200 bg-zinc-50 p-6">
            @csrf
            <label class="form-label">Purchase email</label>
            <input name="buyer_email" type="email" value="{{ old('buyer_email') }}" class="form-input" required>
            @error('buyer_email')<p class="form-error">{{ $message }}</p>@enderror
            <label class="form-label mt-5">Order reference</label>
            <input name="wc_order_id" type="number" value="{{ old('wc_order_id') }}" class="form-input" required>
            @error('wc_order_id')<p class="form-error">{{ $message }}</p>@enderror
            <button class="btn-primary mt-6 w-full">Find order</button>
        </form>
    </div>
@endsection
