<div class="account-table-shell relative">
    @if ($orders->isEmpty())
        <div class="account-empty-state border-0">
            <x-heroicon-o-receipt-percent class="mx-auto size-12 text-zinc-400" />
            <h2 class="mt-4 text-lg font-semibold">No previous orders</h2>
            <p class="mt-2 text-sm text-zinc-600">Start browsing and purchase some software.</p>
        </div>
    @else
        <div class="account-table-wrap overflow-x-auto">
            <table class="account-table min-w-full">
                <thead>
                    <tr>
                        <th class="px-6 py-3">Order</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Amount</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Invoice</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td data-label="Order" class="px-6 py-4 font-mono text-sm text-zinc-900">#{{ $order->reference }}</td>
                            <td data-label="Status" class="px-6 py-4">
                                @php($paymentStatus = $order->payment_status?->label() ?: 'Payment pending')
                                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100"><span class="size-1.5 rounded-full bg-emerald-500"></span>{{ $paymentStatus }}</span>
                            </td>
                            <td data-label="Amount" class="px-6 py-4 text-sm font-semibold text-zinc-900">{{ $order->format('total') }}</td>
                            <td data-label="Date" class="px-6 py-4 text-sm text-zinc-500">{{ $order->placed_at->format('M j, Y') }}</td>
                            <td data-label="Invoice" class="px-6 py-4">
                                <a href="{{ route('account.invoices.show', ['invoiceOrder' => $order->public_id]) }}" class="text-sm font-bold text-teal-800 transition hover:text-teal-600">View invoice</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
