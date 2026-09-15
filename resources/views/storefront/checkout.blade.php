@extends('layouts.storefront')

@section('content')
    @php($name = Str::of($user->name)->squish())
    <div class="mx-auto grid max-w-5xl gap-10 px-5 py-14 lg:grid-cols-[1fr_380px] lg:px-8">
        <section>
            <p class="text-sm font-extrabold uppercase text-teal-700">Checkout</p>
            <h1 class="mt-2 text-4xl font-extrabold">Billing details</h1>
            <p class="mt-4 max-w-xl leading-7 text-zinc-600">Your digital order is processed by Lunar and recorded in your MerebHub account.</p>
            <a href="{{ route('account.billing') }}" class="mt-3 inline-flex text-sm font-semibold text-teal-700 hover:text-teal-900">Manage saved billing details</a>
            <form method="POST" action="{{ route('checkout.store') }}" class="mt-8 grid gap-5 sm:grid-cols-2">
                @csrf
                <input type="hidden" name="country_id" value="{{ $country->id }}">
                <div>
                    <label class="form-label" for="first-name">First name</label>
                    <input id="first-name" name="first_name" value="{{ old('first_name', $billingProfile?->first_name ?? ($name->beforeLast(' ')->toString() ?: $name)) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label" for="last-name">Last name</label>
                    <input id="last-name" name="last_name" value="{{ old('last_name', $billingProfile?->last_name ?? ($name->contains(' ') ? $name->afterLast(' ') : '')) }}" class="form-input">
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label" for="contact-email">Email</label>
                    <input id="contact-email" name="contact_email" type="email" value="{{ old('contact_email', $billingProfile?->contact_email ?? $user->email) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label" for="company-name">Company (optional)</label>
                    <input id="company-name" name="company_name" value="{{ old('company_name', $billingProfile?->company_name) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="tax-identifier">Tax ID (optional)</label>
                    <input id="tax-identifier" name="tax_identifier" value="{{ old('tax_identifier', $billingProfile?->tax_identifier) }}" class="form-input">
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label" for="contact-phone">Phone (optional)</label>
                    <input id="contact-phone" name="contact_phone" type="tel" value="{{ old('contact_phone', $billingProfile?->contact_phone) }}" class="form-input">
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label" for="line-one">Billing address</label>
                    <input id="line-one" name="line_one" value="{{ old('line_one', $billingProfile?->line_one) }}" class="form-input" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label" for="line-two">Address line 2 (optional)</label>
                    <input id="line-two" name="line_two" value="{{ old('line_two', $billingProfile?->line_two) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="city">City</label>
                    <input id="city" name="city" value="{{ old('city', $billingProfile?->city ?? 'Addis Ababa') }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label" for="state">Region (optional)</label>
                    <input id="state" name="state" value="{{ old('state', $billingProfile?->state) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="postcode">Postal code</label>
                    <input id="postcode" name="postcode" value="{{ old('postcode', $billingProfile?->postcode ?? '1000') }}" class="form-input" required>
                </div>
                <fieldset class="sm:col-span-2">
                    <legend class="form-label">Payment method</legend>
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-teal-300 bg-teal-50/60 p-4 ring-1 ring-teal-100">
                        <input name="payment_method" type="radio" value="chapa" class="mt-1 border-zinc-300 text-teal-600 focus:ring-teal-500" required @checked(old('payment_method', 'chapa') === 'chapa')>
                        <span>
                            <span class="block text-sm font-extrabold text-zinc-900">Pay securely with Chapa</span>
                            <span class="mt-1 block text-xs leading-5 text-zinc-600">You will be redirected to Chapa to complete your payment by card or supported mobile money method.</span>
                        </span>
                    </label>
                    @error('payment_method')<p class="form-error">{{ $message }}</p>@enderror
                </fieldset>
                <button type="submit" class="btn-dark sm:col-span-2">Continue to payment <x-heroicon-o-arrow-right class="size-4" /></button>
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
