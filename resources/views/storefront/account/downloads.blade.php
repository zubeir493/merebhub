@extends('storefront.account.layout')

@section('account-content')
    <header class="account-page-header">
        <h1 class="account-page-title">Downloads</h1>
        <p class="account-page-description">Download installers and files included with your active purchases.</p>
    </header>

    @if ($downloads->isEmpty())
        <section class="account-empty-state">
            <x-heroicon-o-arrow-down-tray class="mx-auto size-8 text-zinc-400" aria-hidden="true" />
            <h2 class="mt-5 text-xl font-extrabold text-zinc-950">No downloads available</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-zinc-600">When a purchased product includes downloadable files, they will appear here.</p>
            <a href="{{ route('store.index') }}" class="btn-primary mt-6">Browse software</a>
        </section>
    @else
        <div class="account-table-shell account-table-wrap">
            <div class="overflow-x-auto">
                <table class="account-table min-w-full w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th class="px-5 py-3">File</th>
                            <th class="px-5 py-3">Product</th>
                            <th class="px-5 py-3">Size</th>
                            <th class="px-5 py-3">Updated</th>
                            <th class="px-5 py-3 text-right" aria-label="Actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($downloads as $download)
                            <tr class="align-middle transition-colors hover:bg-zinc-50">
                                <td data-label="File" class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-10 shrink-0 place-items-center rounded-md bg-teal-50 text-teal-800">
                                            <x-heroicon-o-archive-box-arrow-down class="size-5" aria-hidden="true" />
                                        </span>
                                        <span class="min-w-0">
                                            <strong class="block truncate text-zinc-950">{{ $download->filename }}</strong>
                                            <span class="mt-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ str(pathinfo($download->filename, PATHINFO_EXTENSION) ?: 'file')->limit(10) }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Product" class="px-5 py-4 font-semibold text-zinc-700">
                                    @if ($download->product)
                                        <a href="{{ route('products.show', $download->product) }}" class="transition hover:text-teal-700">{{ $download->product->name }}</a>
                                    @else
                                        Digital product
                                    @endif
                                </td>
                                <td data-label="Size" class="px-5 py-4 text-zinc-600 tabular-nums">{{ $download->size ? \Illuminate\Support\Number::fileSize($download->size) : '—' }}</td>
                                <td data-label="Updated" class="px-5 py-4 text-zinc-600 tabular-nums">{{ $download->updated_at->format('M j, Y') }}</td>
                                <td data-label="" class="px-5 py-4 text-right">
                                    @if (auth()->user()->hasVerifiedEmail())
                                        <button type="button" class="btn-dark whitespace-nowrap" data-download-asset="{{ route('downloads.url', ['downloadableAsset' => $download->public_id]) }}">
                                            <x-heroicon-o-arrow-down-tray class="size-4" aria-hidden="true" />
                                            Download
                                        </button>
                                    @else
                                        <a href="{{ route('verification.notice') }}" class="text-xs font-extrabold text-teal-800 transition hover:text-teal-600">Verify email to download</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $downloads->links() }}</div>
    @endif

    <p class="mt-4 hidden rounded-lg bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800" data-account-action-error role="alert"></p>
@endsection
