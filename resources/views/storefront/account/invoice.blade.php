@extends('storefront.account.layout')

@section('account-content')
    <div class="flex flex-wrap items-center justify-between gap-4 print:hidden">
        <a href="{{ route('account.invoices.index') }}" class="text-sm font-bold text-teal-800 underline decoration-teal-300 underline-offset-4">Back to invoices</a>
        <button type="button" data-print-invoice class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-bold hover:bg-zinc-50">Print / Save PDF</button>
    </div>

    <article class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-10 print:mt-0 print:border-0 print:p-0 print:shadow-none">
        <header class="flex flex-col gap-6 border-b border-zinc-200 pb-8 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-teal-700">MerebHub · Invoice</p>
                <h1 class="mt-2 text-3xl font-extrabold">{{ $invoice->invoice_number }}</h1>
                <p class="mt-2 text-sm text-zinc-600">Issued {{ $invoice->issued_at->format('F j, Y') }}</p>
                <p class="mt-1 text-sm text-zinc-600">Order {{ $invoice->order->reference }} · {{ str($invoice->payment_status)->replace('_', ' ')->title() }}</p>
            </div>
            <div class="text-sm text-zinc-700 sm:text-right">
                <h2 class="font-bold text-zinc-950">Customer</h2>
                @if ($invoice->billing_snapshot['company_name'])
                    <p class="mt-2 font-semibold">{{ $invoice->billing_snapshot['company_name'] }}</p>
                @endif
                <p class="{{ $invoice->billing_snapshot['company_name'] ? 'mt-1' : 'mt-2' }}">{{ trim(($invoice->billing_snapshot['first_name'] ?? '').' '.($invoice->billing_snapshot['last_name'] ?? '')) }}</p>
                @if ($invoice->billing_snapshot['contact_email'])
                    <p>{{ $invoice->billing_snapshot['contact_email'] }}</p>
                @endif
            </div>
        </header>

        <div class="mt-8 overflow-x-auto">
            <table class="min-w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 print:bg-transparent">
                    <tr class="text-left text-xs uppercase tracking-wide text-zinc-500">
                        <th class="px-3 py-3">Description</th>
                        <th class="px-3 py-3 text-right">Qty</th>
                        <th class="px-3 py-3 text-right">Unit price</th>
                        <th class="px-3 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($invoice->line_items as $line)
                        <tr>
                            <td class="px-3 py-4">
                                <p class="font-semibold">{{ $line['description'] }}</p>
                                @if ($line['option'])
                                    <p class="mt-1 text-xs text-zinc-500">{{ $line['option'] }}</p>
                                @endif
                                @if ($line['identifier'])
                                    <p class="mt-1 font-mono text-xs text-zinc-500">{{ $line['identifier'] }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-4 text-right">{{ $line['quantity'] }}</td>
                            <td class="px-3 py-4 text-right">{{ number_format($line['unit_price'] / $invoice->currency_factor, $invoice->currency_decimal_places, '.', ',') }}</td>
                            <td class="px-3 py-4 text-right font-semibold">{{ number_format($line['total'] / $invoice->currency_factor, $invoice->currency_decimal_places, '.', ',') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <dl class="ml-auto mt-8 grid max-w-sm gap-3 text-sm">
            <div class="flex justify-between gap-6"><dt class="text-zinc-600">Subtotal</dt><dd>{{ number_format($invoice->subtotal / $invoice->currency_factor, $invoice->currency_decimal_places, '.', ',') }} {{ $invoice->currency_code }}</dd></div>
            <div class="flex justify-between gap-6"><dt class="text-zinc-600">Discounts</dt><dd>−{{ number_format($invoice->discount_total / $invoice->currency_factor, $invoice->currency_decimal_places, '.', ',') }} {{ $invoice->currency_code }}</dd></div>
            <div class="flex justify-between gap-6"><dt class="text-zinc-600">Tax</dt><dd>{{ number_format($invoice->tax_total / $invoice->currency_factor, $invoice->currency_decimal_places, '.', ',') }} {{ $invoice->currency_code }}</dd></div>
            <div class="flex justify-between gap-6"><dt class="text-zinc-600">Shipping</dt><dd>{{ number_format($invoice->shipping_total / $invoice->currency_factor, $invoice->currency_decimal_places, '.', ',') }} {{ $invoice->currency_code }}</dd></div>
            <div class="flex justify-between gap-6 border-t border-zinc-200 pt-3 text-base font-extrabold"><dt>Total</dt><dd>{{ number_format($invoice->total / $invoice->currency_factor, $invoice->currency_decimal_places, '.', ',') }} {{ $invoice->currency_code }}</dd></div>
        </dl>
    </article>
@endsection
