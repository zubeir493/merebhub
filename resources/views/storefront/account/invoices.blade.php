@extends('storefront.account.layout')

@section('account-content')
    <header class="mb-8">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-teal-700">Billing history</p>
        <h1 class="mt-2 text-4xl font-extrabold tracking-tight">Invoices</h1>
        <p class="mt-2 max-w-2xl text-zinc-600">View printable invoices for your completed orders.</p>
    </header>

    @if ($invoices->isEmpty())
        <section class="rounded-2xl border border-zinc-200 bg-white px-6 py-16 text-center">
            <x-heroicon-o-document-text class="mx-auto size-12 text-zinc-400" aria-hidden="true" />
            <h2 class="mt-4 text-xl font-bold">No invoices yet</h2>
            <p class="mt-2 text-sm text-zinc-600">Invoices for placed orders will appear here.</p>
        </section>
    @else
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-zinc-100 bg-zinc-50">
                        <tr class="text-left text-xs uppercase tracking-wide text-zinc-500">
                            <th class="px-5 py-3">Invoice</th>
                            <th class="px-5 py-3">Order</th>
                            <th class="px-5 py-3">Payment</th>
                            <th class="px-5 py-3">Total</th>
                            <th class="px-5 py-3">Issued</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td class="px-5 py-4">
                                    <a href="{{ route('account.invoices.show', ['invoiceOrder' => $invoice->order->public_id]) }}" class="font-mono text-sm font-bold text-teal-800 underline decoration-teal-300 underline-offset-4">{{ $invoice->invoice_number }}</a>
                                </td>
                                <td class="px-5 py-4 font-mono text-sm">{{ $invoice->order->reference }}</td>
                                <td class="px-5 py-4 text-sm">{{ str($invoice->payment_status)->replace('_', ' ')->title() }}</td>
                                <td class="px-5 py-4 text-sm font-bold">{{ number_format($invoice->total / $invoice->currency_factor, $invoice->currency_decimal_places, '.', ',') }} {{ $invoice->currency_code }}</td>
                                <td class="px-5 py-4 text-sm text-zinc-600">{{ $invoice->issued_at->format('M j, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
@endsection
