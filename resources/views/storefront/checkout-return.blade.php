@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-6xl px-5 py-12 lg:px-8">
        {{-- Confirmation Header --}}
        <div class="border-b border-zinc-200 pb-10">
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-xs font-bold text-teal-800">
                    <span class="size-2 rounded-full bg-teal-500 animate-pulse"></span>
                    Payment confirmed
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-semibold text-zinc-600">
                    <x-heroicon-o-shield-check class="size-3.5 text-teal-700" />
                    Licenses ready in your account
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-semibold text-zinc-600">
                    <x-heroicon-o-calendar class="size-3.5 text-zinc-500" />
                    {{ $order->created_at->format('M j, Y') }}
                </span>
            </div>

            <div class="mt-5 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-4xl font-extrabold tracking-[-0.04em] text-zinc-950 sm:text-5xl lg:text-6xl text-balance">
                        Your software is ready.
                    </h1>
                    <p class="mt-3 max-w-2xl text-base font-medium leading-relaxed text-zinc-600 sm:text-lg">
                        Payment is confirmed. Your licenses, downloads, and receipt are now available in your MerebHub account.
                    </p>
                </div>
                <div class="shrink-0" x-data="{ copied: false }">
                    <div class="flex items-center content-start gap-2 rounded-md border border-zinc-200 bg-zinc-50 p-2">
                        <div class="px-2">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-zinc-400">Order reference</span>
                            <span class="font-mono text-sm font-extrabold text-zinc-900">#{{ $order->reference }}</span>
                        </div>
                        <button
                            type="button"
                            @click="navigator.clipboard.writeText('{{ $order->reference }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="grid size-9 place-items-center rounded-md border border-zinc-200 bg-white text-zinc-600 transition hover:border-teal-500 hover:text-teal-700 hover:bg-teal-50"
                            :title="copied ? 'Copied!' : 'Copy order reference'"
                            aria-label="Copy order reference"
                        >
                            <x-heroicon-o-check x-show="copied" x-cloak class="size-4 text-emerald-600" />
                            <x-heroicon-o-document-duplicate x-show="! copied" class="size-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2-Column Responsive Layout --}}
        <div class="mt-8 grid gap-10 lg:grid-cols-[1fr_360px]">
            {{-- Left Column: Purchased Items & Activation Guide --}}
            <div class="space-y-10 min-w-0">
                {{-- Purchased Software Section --}}
                @if ($order->productLines->isNotEmpty())
                    <div>
                        <div class="flex items-end justify-between gap-4 border-b border-zinc-200 pb-4">
                            <h2 class="text-lg font-extrabold text-zinc-950">
                                Your software ({{ $order->productLines->count() }})
                            </h2>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-teal-200 bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-800">
                                <span class="size-1.5 rounded-full bg-teal-500"></span>
                                Ready in your account
                            </span>
                        </div>

                        <div class="account-table-shell account-table-wrap">
                            <div class="overflow-x-auto">
                                <table class="account-table min-w-full w-full text-left text-sm">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-3 sm:px-5">Product</th>
                                            <th class="px-3 py-3 text-center sm:px-4">Qty</th>
                                            <th class="px-3 py-3 text-right sm:px-4">Total</th>
                                            <th class="px-4 py-3 text-right sm:px-5" aria-label="Action"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->productLines as $line)
                                            @php
                                                $variant = $line->purchasable;
                                                $product = isset($products) ? $products->get($variant?->product_id) : null;
                                                if (! $product) {
                                                    $product = $variant?->product instanceof \App\Models\Product
                                                        ? $variant->product
                                                        : ($variant?->product ? \App\Models\Product::query()->find($variant->product->id) : null);
                                                }
                                                $cover = $product?->coverUrl();
                                                $variantName = $product && $variant instanceof \Lunar\Core\Models\ProductVariant
                                                    ? $product->variantDisplayName($variant)
                                                    : null;
                                                $productName = $product?->name ?? $line->description;
                                                $lineTitle = $variantName ? "{$productName} - {$variantName}" : $productName;
                                            @endphp
                                            <tr class="transition-colors hover:bg-zinc-50/70">
                                                <td data-label="Product" class="px-4 py-3.5 sm:px-5 sm:py-4 max-lg:!items-center">
                                                    <div class="flex items-center gap-3 min-w-0">
                                                        @if ($cover)
                                                            <img src="{{ $cover }}" alt="" class="size-9 shrink-0 rounded-sm bg-zinc-100 object-cover ring-1 ring-zinc-950/10">
                                                        @else
                                                            <div class="grid size-9 shrink-0 place-items-center rounded-sm bg-zinc-100 text-zinc-500 ring-1 ring-zinc-950/10">
                                                                <x-heroicon-o-cube class="size-4" />
                                                            </div>
                                                        @endif
                                                        @if ($product)
                                                            <a href="{{ route('products.show', $product) }}" class="truncate text-sm font-extrabold text-zinc-950 hover:text-teal-700 transition">
                                                                {{ $lineTitle }}
                                                            </a>
                                                        @else
                                                            <span class="truncate text-sm font-extrabold text-zinc-950">
                                                                {{ $lineTitle }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td data-label="Qty" class="px-3 py-3.5 text-center text-sm font-semibold text-zinc-700 sm:px-4 sm:py-4">
                                                    {{ $line->quantity }}
                                                </td>
                                                <td data-label="Total" class="px-3 py-3.5 text-right text-sm font-extrabold tabular-nums text-zinc-950 sm:px-4 sm:py-4">
                                                    {{ $line->format('sub_total') }}
                                                </td>
                                                <td data-label="Action" class="px-4 py-3.5 text-right sm:px-5 sm:py-4">
                                                    <a href="{{ route('account.purchases') }}" class="text-sm font-bold text-teal-800 transition hover:text-teal-600">
                                                        Open license &rarr;
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="space-y-2 border-b border-zinc-200 py-3.5 text-sm">
                            @if (! empty($hasDiscount) || ($order->discount_total > 0))
                                <div class="flex items-center justify-between text-zinc-600">
                                    <span>Items subtotal</span>
                                    <span class="font-semibold tabular-nums text-zinc-950">{{ $order->format('sub_total') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-emerald-700 font-semibold">
                                    <span class="flex items-center gap-1.5">
                                        <x-heroicon-s-tag class="size-3.5 text-emerald-600 shrink-0" />
                                        <span>Discount</span>
                                        @if (! empty($couponCode))
                                            <span class="inline-flex items-center rounded-md border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 font-mono text-[11px] font-bold uppercase tracking-wider text-emerald-800">
                                                {{ $couponCode }}
                                            </span>
                                        @elseif (! empty($discountName))
                                            <span class="text-xs font-medium text-emerald-700">
                                                ({{ $discountName }})
                                            </span>
                                        @endif
                                    </span>
                                    <span class="tabular-nums font-extrabold">-{{ $discountFormatted ?? $order->format('discount_total') }}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between pt-1 font-bold text-zinc-700">
                                <span>Total items paid</span>
                                <strong class="text-base font-extrabold tabular-nums text-zinc-950">{{ $order->format('total') }}</strong>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- 3-Step Activation Guide --}}
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-6 sm:p-7">
                    <div class="flex items-center gap-3">
                        <span class="grid size-8 place-items-center rounded-md bg-teal-600 text-white">
                            <x-heroicon-o-sparkles class="size-4" />
                        </span>
                        <div>
                            <h3 class="text-base font-extrabold text-zinc-950">Start using your software</h3>
                            <p class="text-xs font-semibold text-zinc-600">Three quick steps from purchase to first launch.</p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-md border border-zinc-200 bg-white p-4">
                            <div class="flex size-6 items-center justify-center rounded-full bg-teal-100 text-xs font-black text-teal-900">1</div>
                            <h4 class="mt-2.5 text-sm font-extrabold text-zinc-950">Open your license key</h4>
                            <p class="mt-1 text-xs leading-relaxed text-zinc-600">Open Purchases & Licenses to reveal or copy your key whenever you need it.</p>
                        </div>
                        <div class="rounded-md border border-zinc-200 bg-white p-4">
                            <div class="flex size-6 items-center justify-center rounded-full bg-teal-100 text-xs font-black text-teal-900">2</div>
                            <h4 class="mt-2.5 text-sm font-extrabold text-zinc-950">Download your software</h4>
                            <p class="mt-1 text-xs leading-relaxed text-zinc-600">Choose the build for your machine and download it from your account.</p>
                        </div>
                        <div class="rounded-md border border-zinc-200 bg-white p-4">
                            <div class="flex size-6 items-center justify-center rounded-full bg-teal-100 text-xs font-black text-teal-900">3</div>
                            <h4 class="mt-2.5 text-sm font-extrabold text-zinc-950">Activate and get to work</h4>
                            <p class="mt-1 text-xs leading-relaxed text-zinc-600">Open the app, enter your key, and start working. Offline activation is supported too.</p>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-4 border-t border-zinc-200 pt-4 text-xs text-zinc-700">
                        <span class="flex items-center gap-1.5 font-semibold">
                            <x-heroicon-o-shield-check class="size-4 text-teal-700" />
                            Need to activate without an internet connection?
                        </span>
                        <a href="{{ route('offline-activation.show') }}" class="font-extrabold text-teal-800 hover:text-teal-950 underline underline-offset-4">
                            Create an offline license file &rarr;
                        </a>
                    </div>
                </div>
            </div>

            {{-- Right Column: Order Receipt & Quick Actions --}}
            <aside class="h-fit space-y-6 lg:sticky lg:top-28">
                <div class="rounded-lg border border-zinc-200 bg-white p-6">
                    <h2 class="text-base font-extrabold text-zinc-950">Order summary</h2>

                    <div class="mt-5 space-y-3 border-b border-zinc-200 pb-5 text-sm">
                        <div class="flex items-center justify-between text-zinc-600">
                            <span>Order reference</span>
                            <span class="font-mono text-xs font-bold text-zinc-900">{{ $order->reference }}</span>
                        </div>
                        <div class="flex items-center justify-between text-zinc-600">
                            <span>Status</span>
                            <span class="inline-flex items-center gap-1 font-bold text-emerald-700">
                                <span class="size-1.5 rounded-full bg-emerald-500"></span> Confirmed
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-zinc-600">
                            <span>Date</span>
                            <span class="font-semibold text-zinc-900">{{ $order->created_at->format('M j, Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-zinc-600">
                            <span>Account</span>
                            <span class="truncate max-w-[170px] font-semibold text-zinc-900" title="{{ $order->user?->email ?? auth()->user()?->email }}">
                                {{ $order->user?->email ?? auth()->user()?->email }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-zinc-600">
                            <span>Payment gateway</span>
                            <span class="font-semibold text-zinc-900">Chapa (Br)</span>
                        </div>
                    </div>

                    <div class="mt-5 space-y-2.5 text-sm">
                        <div class="flex items-center justify-between text-zinc-600">
                            <span>Subtotal</span>
                            <strong class="tabular-nums text-zinc-950">{{ $order->format('sub_total') }}</strong>
                        </div>
                        @if (! empty($hasDiscount) || ($order->discount_total > 0))
                            <div class="flex items-center justify-between text-emerald-700 font-semibold">
                                <span class="flex items-center gap-1.5">
                                    <x-heroicon-s-tag class="size-3.5 text-emerald-600 shrink-0" />
                                    <span>Discount</span>
                                    @if (! empty($couponCode))
                                        <span class="inline-flex items-center rounded-md border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 font-mono text-[11px] font-bold uppercase tracking-wider text-emerald-800">
                                            {{ $couponCode }}
                                        </span>
                                    @elseif (! empty($discountName))
                                        <span class="text-xs font-medium text-emerald-700">
                                            ({{ $discountName }})
                                        </span>
                                    @endif
                                </span>
                                <strong class="tabular-nums font-extrabold">-{{ $discountFormatted ?? $order->format('discount_total') }}</strong>
                            </div>
                        @endif
                        @if ($order->shipping_total > 0)
                            <div class="flex items-center justify-between text-zinc-600">
                                <span>Shipping</span>
                                <strong class="tabular-nums text-zinc-950">{{ $order->format('shipping_total') }}</strong>
                            </div>
                        @endif
                        @if ($order->tax_total > 0)
                            <div class="flex items-center justify-between text-zinc-600">
                                <span>Tax</span>
                                <strong class="tabular-nums text-zinc-950">{{ $order->format('tax_total') }}</strong>
                            </div>
                        @endif
                        <div class="flex items-end justify-between border-t border-zinc-200 pt-4">
                            <div>
                                <span class="block text-xs font-bold uppercase tracking-wider text-zinc-400">Total Paid</span>
                                <span class="text-xs text-zinc-500">Including taxes</span>
                            </div>
                            <strong class="text-2xl font-black tabular-nums text-zinc-950">{{ $order->format('total') }}</strong>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col gap-2.5">
                        <a href="{{ route('account.purchases') }}" class="btn-primary w-full">
                            <x-heroicon-o-key class="size-4" /> Open licenses &amp; downloads
                        </a>
                        <a href="{{ route('account.orders') }}" class="btn-dark w-full">
                            <x-heroicon-o-document-text class="size-4" /> View order receipt
                        </a>
                    </div>
                </div>

                {{-- Support Box --}}
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-5">
                    <div class="flex items-start gap-3">
                        <x-heroicon-o-chat-bubble-left-right class="size-5 shrink-0 text-teal-700 mt-0.5" />
                        <div>
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-zinc-900">Have questions?</h4>
                            <p class="mt-1 text-xs leading-relaxed text-zinc-600">Need help installing, activating, or downloading? Our team and verified publishers are ready to help.</p>
                            <a href="{{ route('contact.index') }}" class="mt-2.5 inline-flex items-center gap-1 text-xs font-extrabold text-teal-800 hover:text-teal-950">
                                Get customer support <x-heroicon-o-arrow-right class="size-3" />
                            </a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection

