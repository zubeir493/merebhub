@extends('storefront.account.layout')

@section('account-content')
    <header class="account-page-header">
        <h1 class="account-page-title">Invoices</h1>
        <p class="account-page-description">View printable invoices for your completed orders.</p>
    </header>

    @if ($invoices->isEmpty())
        <section class="account-empty-state">
            <x-heroicon-o-document-text class="mx-auto size-12 text-zinc-400" aria-hidden="true" />
            <h2 class="mt-4 text-xl font-bold">No invoices yet</h2>
            <p class="mt-2 text-sm text-zinc-600">Invoices for placed orders will appear here.</p>
        </section>
    @else
        <div class="account-table-shell account-table-wrap">
            <div class="overflow-x-auto">
                <table class="account-table min-w-full">
                    <thead>
                        <tr>
                            <th class="px-5 py-3">Invoice</th>
                            <th class="px-5 py-3">Order</th>
                            <th class="px-5 py-3">Payment</th>
                            <th class="px-5 py-3">Total</th>
                            <th class="px-5 py-3">Issued</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td data-label="Invoice" class="px-5 py-4">
                                    <a href="{{ route('account.invoices.show', ['invoiceOrder' => $invoice->order->public_id]) }}" class="font-mono text-sm font-bold text-teal-800 transition hover:text-teal-600">{{ $invoice->invoice_number }}</a>
                                </td>
                                <td data-label="Order" class="px-5 py-4 font-mono text-sm">{{ $invoice->order->reference }}</td>
                                <td data-label="Payment" class="px-5 py-4 text-sm">{{ str($invoice->payment_status)->replace('_', ' ')->title() }}</td>
                                <td data-label="Total" class="px-5 py-4 text-sm font-bold">{{ number_format($invoice->total / $invoice->currency_factor, $invoice->currency_decimal_places, '.', ',') }} {{ $invoice->currency_code }}</td>
                                <td data-label="Issued" class="px-5 py-4 text-sm text-zinc-600">{{ $invoice->issued_at->format('M j, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $invoices->links() }}</div>
    @endif
@endsection
