@extends('storefront.account.layout')

@section('account-content')
    <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <header>
            <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-zinc-950">Software licenses</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-600">Manage your purchased licenses, download license files, and keep track of active machines.</p>
        </header>
        <button type="button" data-offline-open data-kgm-offline-open class="btn-primary">Offline activation</button>
    </div>

    @if ($purchases->isEmpty())
        <section class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-10 text-center">
            <h2 class="text-xl font-extrabold text-zinc-950">No licenses yet</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-zinc-600">Purchase software from the shop to get your first license.</p>
            <a href="{{ route('store.index') }}" class="btn-primary mt-6">Browse software</a>
        </section>
    @else
        <div class="account-table-wrap overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="account-table min-w-full w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 text-xs font-extrabold uppercase tracking-wider text-zinc-500">
                        <tr>
                            <th class="px-5 py-3">Product</th>
                            <th class="px-5 py-3">License key</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Devices</th>
                            <th class="px-5 py-3 text-right" aria-label="Actions"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($purchases as $purchase)
                            @php
                                $credentialId = $purchase->credential?->public_id;
                                $licensePreview = $purchase->credential ? str($purchase->credential->secret)->limit(12, '...') : '';
                                $expiresAt = data_get($purchase->meta, 'expires_at');
                                $expiryLabel = filled($expiresAt) ? 'Expires '.\Illuminate\Support\Carbon::parse($expiresAt)->format('M j, Y') : 'Perpetual license';
                                $downloadAssets = $purchase->product?->downloadableAssets ?? collect();
                            @endphp
                            <tr class="kgm-license-card align-middle transition-colors hover:bg-zinc-50" data-kgm-reveal-context data-order-id="{{ $purchase->order?->getKey() }}" data-license-index="{{ $purchase->getKey() }}">
                                <td data-label="Product" class="px-5 py-4">
                                    <div class="font-extrabold text-zinc-950">{{ $purchase->product?->name ?? 'Digital product' }}</div>
                                    <div class="mt-1 text-xs font-semibold text-zinc-500">{{ $purchase->variantDisplayName() }} <span aria-hidden="true">·</span> #{{ $purchase->order?->reference ?? '—' }} - {{ $expiryLabel }}</div>
                                </td>
                                <td data-label="License key" class="max-w-xs px-5 py-4">
                                    @if ($purchase->credential && $purchase->status === 'active')
                                        @if (auth()->user()->hasVerifiedEmail())
                                            <div class="flex w-fit min-w-0 items-center border border-zinc-200  rounded-md overflow-hidden">
                                                <span data-license-value class="min-w-0 truncate px-3 font-mono text-xs font-bold text-zinc-800" title="License key preview">{{ $licensePreview }}</span>
                                                <button type="button" data-mh-copy-license data-reveal-credential="{{ route('credentials.reveal', $credentialId) }}" class="mh-license-copy-button" aria-label="Copy license key" title="Copy license key">
                                                    <x-heroicon-o-document-duplicate class="size-5" aria-hidden="true" />
                                                </button>
                                            </div>
                                        @else
                                            <a href="{{ route('verification.notice') }}" class="text-xs font-bold text-teal-800 underline decoration-teal-300 underline-offset-4">Verify your email to reveal this key</a>
                                        @endif
                                    @else
                                        <span class="text-xs font-semibold text-zinc-500">Not available</span>
                                    @endif
                                </td>
                                <td data-label="Status" class="px-5 py-4">
                                    <span class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-extrabold {{ $purchase->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                        <span class="size-1 rounded-full {{ $purchase->status === 'active' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                        {{ str($purchase->status)->replace('_', ' ')->title() }}
                                    </span>
                                </td>
                                <td data-label="Devices" class="px-5 py-4">
                                    <span class="font-extrabold tabular-nums text-zinc-900">{{ (int) data_get($purchase->meta, 'machines_used', 0) }} / {{ (int) data_get($purchase->meta, 'max_machines', 3) }}</span>
                                    <span class="mt-1 block text-xs text-zinc-500">active</span>
                                </td>
                                <td data-label="" class="px-5 py-4 text-right">
                                    <div class="flex flex-wrap justify-end gap-x-3 gap-y-2">
                                        @if ($purchase->credential && auth()->user()->hasVerifiedEmail() && $purchase->status === 'active')
                                            <a href="{{ route('credentials.license-text', $credentialId) }}" class="inline-flex items-center gap-1 text-xs font-extrabold text-zinc-700 underline decoration-zinc-300 underline-offset-4 hover:text-teal-700"><x-heroicon-o-document-text class="size-3.5" aria-hidden="true" />License TXT</a>
                                        @endif
                                        @if (auth()->user()->hasVerifiedEmail() && $purchase->status === 'active' && $downloadAssets->isNotEmpty())
                                            @foreach ($downloadAssets as $asset)
                                                <button type="button" class="inline-flex items-center gap-1 text-xs font-extrabold text-teal-800 underline decoration-teal-300 underline-offset-4 hover:text-teal-950" data-download-asset="{{ route('downloads.url', ['downloadableAsset' => $asset->public_id]) }}"><x-heroicon-o-arrow-down-tray class="size-3.5" aria-hidden="true" />{{ $asset->filename }}</button>
                                            @endforeach
                                        @endif
                                        @if ($downloadAssets->isEmpty())
                                            <span class="text-xs font-semibold text-zinc-500">No downloads yet</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $purchases->links() }}</div>
    @endif

    <p class="mt-4 hidden rounded-lg bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800" data-account-action-error role="alert"></p>

    <div id="kgm-offline-modal" data-offline-modal data-kgm-offline-modal data-mh-hidden class="fixed inset-0 z-[80] grid place-items-center bg-zinc-950/40 p-4 backdrop-blur-[2px]" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="kgm-offline-title">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-[0_24px_70px_oklch(0_0_0/0.22)] sm:p-7">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <span class="text-xs font-extrabold uppercase tracking-widest text-teal-700">Activation helper</span>
                    <h2 id="kgm-offline-title" class="mt-2 text-xl font-extrabold text-zinc-950">Offline .lic activation</h2>
                </div>
                <button type="button" data-offline-close data-kgm-offline-close class="grid size-9 place-items-center rounded-lg text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700" aria-label="Close offline activation">×</button>
            </div>
            <p class="mt-3 text-sm leading-6 text-zinc-600">Export a .lreq request file from the desktop app, upload it here, then import the generated .lic file back into the app.</p>
            <form class="mt-5 space-y-4" method="POST" action="{{ route('credentials.offline-file') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="kgm_offline_license_file" value="1">
                <label class="block"><span class="form-label">Request file</span><input class="form-input" type="file" name="offline_request" accept=".lreq,text/plain" required></label>
                @error('offline_request')
                    <p class="form-error">{{ $message }}</p>
                @enderror
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" data-offline-close data-kgm-offline-close class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-extrabold text-zinc-600 transition hover:bg-zinc-100">Cancel</button>
                    <button type="submit" class="btn-dark">Generate .lic file</button>
                </div>
            </form>
        </div>
    </div>
@endsection
