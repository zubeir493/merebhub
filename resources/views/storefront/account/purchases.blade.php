@extends('storefront.account.layout')

@section('account-content')
    <header class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-teal-700">Your library</p>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight">Purchases &amp; licenses</h1>
            <p class="mt-2 max-w-2xl text-zinc-600">Find your product access, license keys, and available downloads in one place.</p>
        </div>
        <div class="flex flex-wrap gap-4">
            <a href="{{ route('account.invoices.index') }}" class="text-sm font-bold text-teal-800 underline decoration-teal-300 underline-offset-4">View invoices</a>
            <a href="{{ route('account.orders') }}" class="text-sm font-bold text-teal-800 underline decoration-teal-300 underline-offset-4">View order history</a>
        </div>
    </header>

    @if ($purchases->isEmpty())
        <section class="rounded-2xl border border-zinc-200 bg-white px-6 py-16 text-center">
            <x-heroicon-o-archive-box class="mx-auto size-12 text-zinc-400" aria-hidden="true" />
            <h2 class="mt-4 text-xl font-bold">Your library is empty</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-zinc-600">Products you purchase will appear here with their license details and downloads.</p>
            <a href="{{ route('store.index') }}" class="btn-primary mt-6">Browse the marketplace</a>
        </section>
    @else
        <div class="grid gap-5">
            @foreach ($purchases as $purchase)
                <article class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-4 border-b border-zinc-100 bg-zinc-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <h2 class="text-lg font-extrabold">{{ $purchase->product?->name ?? 'Digital product' }}</h2>
                            <p class="mt-1 text-sm text-zinc-600">
                                Order <span class="font-mono font-semibold text-zinc-800">{{ $purchase->order?->reference ?? '—' }}</span>
                                <span class="px-1 text-zinc-400" aria-hidden="true">·</span>
                                {{ $purchase->created_at->format('M j, Y') }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            @if ($purchase->order)
                                <a href="{{ route('account.invoices.show', ['invoiceOrder' => $purchase->order->public_id]) }}" class="text-sm font-bold text-teal-800 underline decoration-teal-300 underline-offset-4">Invoice</a>
                            @endif
                            <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-bold {{ $purchase->status === 'active' ? 'bg-teal-50 text-teal-800 ring-1 ring-teal-200' : 'bg-zinc-100 text-zinc-700 ring-1 ring-zinc-200' }}">
                                {{ str($purchase->status)->replace('_', ' ')->title() }}
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-6 px-5 py-5 sm:px-6 sm:py-6 lg:grid-cols-2">
                        @if ($purchase->credential)
                            <section aria-labelledby="license-{{ $purchase->public_id }}">
                                <h3 id="license-{{ $purchase->public_id }}" class="text-sm font-extrabold text-zinc-900">License key</h3>
                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    @if ($purchase->status === 'active')
                                        @if (auth()->user()->hasVerifiedEmail())
                                            <button
                                                type="button"
                                                class="btn-dark"
                                                data-reveal-credential="{{ route('credentials.reveal', $purchase->credential->public_id) }}"
                                                data-output-id="credential-{{ $purchase->credential->public_id }}"
                                            >Reveal license key</button>
                                        @else
                                            <a href="{{ route('verification.notice') }}" class="text-sm font-bold text-teal-800 underline decoration-teal-300 underline-offset-4">Verify your email to reveal this key</a>
                                        @endif
                                    @else
                                        <p class="text-sm text-zinc-600">This license is not currently active.</p>
                                    @endif
                                    <code id="credential-{{ $purchase->credential->public_id }}" class="hidden max-w-full break-all rounded-lg bg-zinc-950 px-3 py-2 font-mono text-sm font-semibold text-teal-200" aria-live="polite"></code>
                                </div>
                            </section>
                        @endif

                        @if (auth()->user()->hasVerifiedEmail() && $purchase->status === 'active' && $purchase->product?->downloadableAssets->isNotEmpty())
                            <section aria-labelledby="downloads-{{ $purchase->public_id }}">
                                <h3 id="downloads-{{ $purchase->public_id }}" class="text-sm font-extrabold text-zinc-900">Downloads</h3>
                                <ul class="mt-3 grid gap-2">
                                    @foreach ($purchase->product->downloadableAssets as $asset)
                                        <li>
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-2 text-sm font-bold text-teal-800 underline decoration-teal-300 underline-offset-4 hover:text-teal-950"
                                                data-download-asset="{{ route('downloads.url', ['downloadableAsset' => $asset->public_id]) }}"
                                            >
                                                <x-heroicon-o-arrow-down-tray class="size-4" aria-hidden="true" />
                                                {{ $asset->filename }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </section>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $purchases->links() }}</div>
    @endif

    <p class="mt-4 hidden rounded-lg bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800" data-account-action-error role="alert"></p>
@endsection
