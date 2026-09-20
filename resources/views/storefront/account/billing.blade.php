@extends('storefront.account.layout')

@section('account-content')
    <header class="account-page-header">
        <h1 class="account-page-title">Billing details</h1>
        <p class="account-page-description">Save the billing details used to prefill your next checkout. Completed orders keep their own billing snapshot.</p>
    </header>

    <form method="POST" action="{{ route('account.billing.update') }}" class="account-form-surface grid gap-6 sm:grid-cols-2">
        @csrf
        @method('PUT')

        <div>
            <label for="billing_type" class="form-label">Billing type</label>
            <select id="billing_type" name="billing_type" class="form-input" required>
                <option value="personal" @selected(old('billing_type', $profile?->billing_type ?? 'personal') === 'personal')>Personal</option>
                <option value="business" @selected(old('billing_type', $profile?->billing_type) === 'business')>Business</option>
            </select>
            @error('billing_type')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="company_name" class="form-label">Company name <span class="font-normal text-zinc-500">(business billing)</span></label>
            <input id="company_name" name="company_name" value="{{ old('company_name', $profile?->company_name) }}" class="form-input" autocomplete="organization">
            @error('company_name')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="tax_identifier" class="form-label">Tax identifier <span class="font-normal text-zinc-500">(optional)</span></label>
            <input id="tax_identifier" name="tax_identifier" value="{{ old('tax_identifier', $profile?->tax_identifier) }}" class="form-input">
            @error('tax_identifier')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="contact_email" class="form-label">Billing email</label>
            <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $profile?->contact_email ?? auth()->user()->email) }}" class="form-input" autocomplete="email" required>
            @error('contact_email')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="first_name" class="form-label">First name</label>
            <input id="first_name" name="first_name" value="{{ old('first_name', $profile?->first_name) }}" class="form-input" autocomplete="given-name" required>
            @error('first_name')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="last_name" class="form-label">Last name</label>
            <input id="last_name" name="last_name" value="{{ old('last_name', $profile?->last_name) }}" class="form-input" autocomplete="family-name">
            @error('last_name')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="contact_phone" class="form-label">Phone <span class="font-normal text-zinc-500">(optional)</span></label>
            <input id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $profile?->contact_phone) }}" class="form-input" autocomplete="tel">
            @error('contact_phone')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="country_iso3" class="form-label">Country</label>
            <select id="country_iso3" name="country_iso3" class="form-input" required>
                @foreach ($countries as $country)
                    <option value="{{ $country->iso3 }}" @selected(old('country_iso3', $profile?->country_iso3 ?? 'ETH') === $country->iso3)>{{ $country->name }}</option>
                @endforeach
            </select>
            @error('country_iso3')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label for="line_one" class="form-label">Address line 1</label>
            <input id="line_one" name="line_one" value="{{ old('line_one', $profile?->line_one) }}" class="form-input" autocomplete="address-line1" required>
            @error('line_one')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label for="line_two" class="form-label">Address line 2 <span class="font-normal text-zinc-500">(optional)</span></label>
            <input id="line_two" name="line_two" value="{{ old('line_two', $profile?->line_two) }}" class="form-input" autocomplete="address-line2">
            @error('line_two')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="city" class="form-label">City</label>
            <input id="city" name="city" value="{{ old('city', $profile?->city) }}" class="form-input" autocomplete="address-level2" required>
            @error('city')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="state" class="form-label">Region / state <span class="font-normal text-zinc-500">(optional)</span></label>
            <input id="state" name="state" value="{{ old('state', $profile?->state) }}" class="form-input" autocomplete="address-level1">
            @error('state')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="postcode" class="form-label">Postal code</label>
            <input id="postcode" name="postcode" value="{{ old('postcode', $profile?->postcode) }}" class="form-input" autocomplete="postal-code" required>
            @error('postcode')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-end">
            <button type="submit" class="btn-dark">
                <x-heroicon-o-check-circle class="size-4" aria-hidden="true" />
                Save billing details
            </button>
        </div>
    </form>
@endsection
