<div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white">
    @if ($orders->isEmpty())
        <div class="bg-zinc-50 py-16 text-center">
            <x-heroicon-o-receipt-percent class="mx-auto size-12 text-zinc-400" />
            <h2 class="mt-4 text-lg font-semibold">No previous orders</h2>
            <p class="mt-2 text-sm text-zinc-600">Start browsing and purchase some software.</p>
        </div>
    @else
        <div class="account-table-wrap overflow-x-auto">
            <table class="account-table min-w-full">
                <thead class="border-b border-zinc-100 bg-zinc-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-zinc-500">
                        <th class="px-6 py-3">Products</th>
                        <th class="px-6 py-3">Order</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Amount</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Invoice</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($orders as $order)
                        <tr>
                            <td data-label="Products" class="px-6 py-4">
                                <div class="grid gap-1 text-sm font-semibold text-zinc-900">
                                    @foreach ($order->productLines as $line)
                                        @php($variant = $line->purchasable)
                                        <span>{{ $line->description }} · {{ $variant instanceof \Lunar\Core\Models\ProductVariant ? \App\Models\Product::displayVariantName($variant) : ($line->option ?: 'Standard license') }} × {{ $line->quantity }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td data-label="Order" class="px-6 py-4 font-mono text-sm text-zinc-900">{{ $order->reference }}</td>
                            <td data-label="Status" class="px-6 py-4">
                                @php($paymentStatus = $order->payment_status?->label() ?: 'Payment pending')
                                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100"><span class="size-1.5 rounded-full bg-emerald-500"></span>{{ $paymentStatus }}</span>
                            </td>
                            <td data-label="Amount" class="px-6 py-4 text-sm font-semibold text-zinc-900">{{ $order->format('total') }}</td>
                            <td data-label="Date" class="px-6 py-4 text-sm text-zinc-500">{{ $order->placed_at->format('M j, Y') }}</td>
                            <td data-label="Invoice" class="px-6 py-4">
                                <a href="{{ route('account.invoices.show', ['invoiceOrder' => $order->public_id]) }}" class="text-sm font-bold text-teal-800 underline decoration-teal-300 underline-offset-4">View invoice</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
