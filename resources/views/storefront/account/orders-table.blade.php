<div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white">
    @if ($orders->isEmpty())
        <div class="bg-zinc-50 py-16 text-center">
            <x-heroicon-o-receipt-percent class="mx-auto size-12 text-zinc-400" />
            <h2 class="mt-4 text-lg font-semibold">No previous orders</h2>
            <p class="mt-2 text-sm text-zinc-600">Start browsing and purchase some software.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="border-b border-zinc-100 bg-zinc-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-zinc-500">
                        <th class="px-6 py-3">Products</th>
                        <th class="px-6 py-3">Order</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Amount</th>
                        <th class="px-6 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($orders as $order)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="grid gap-1 text-sm font-semibold text-zinc-900">
                                    @foreach ($order->productLines as $line)
                                        <span>{{ $line->description }} × {{ $line->quantity }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-sm text-zinc-900">{{ $order->reference }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700 ring-1 ring-teal-100">{{ Str::headline($order->status) }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-zinc-900">{{ $order->total->format() }}</td>
                            <td class="px-6 py-4 text-sm text-zinc-500">{{ $order->placed_at->format('M j, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
