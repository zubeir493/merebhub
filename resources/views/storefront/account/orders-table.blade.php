<div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white">
    @if ($orders->isEmpty())
        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 py-16 text-center">
            <x-heroicon-o-receipt-percent class="mx-auto size-12 text-zinc-400" />
            <h2 class="mt-4 text-lg font-semibold">No previous orders</h2>
            <p class="mt-2 text-sm text-zinc-600">Start browsing and purchase some software.</p>
        </div>
    @else
        <table class="min-w-full table-fixed">
            <thead class="border-b border-zinc-100 bg-zinc-50">
                <tr class="text-left text-sm text-zinc-500">
                    <th class="px-6 py-3">PRODUCT</th>
                    <th class="px-6 py-3">ORDER</th>
                    <th class="px-6 py-3">STATUS</th>
                    <th class="px-6 py-3">SEATS</th>
                    <th class="px-6 py-3">AMOUNT</th>
                    <th class="px-6 py-3">DATE</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach ($orders as $order)
                    @php
                        $displayItems = $order->items->isNotEmpty() ? $order->items : collect([(object) ['product' => $order->product, 'quantity' => 1, 'license' => $order->license]]);
                        $first = $displayItems->first();
                        $seats = $displayItems->sum('quantity');
                        $color = method_exists($order->status, 'getColor') ? $order->status->getColor() : 'gray';
                        $badgeClasses = match ($color) {
                            'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                            'warning' => 'bg-amber-50 text-amber-800 ring-amber-100',
                            'danger' => 'bg-rose-50 text-rose-700 ring-rose-100',
                            'info' => 'bg-sky-50 text-sky-700 ring-sky-100',
                            'gray' => 'bg-zinc-50 text-zinc-700 ring-zinc-100',
                            default => 'bg-white text-zinc-700 ring-zinc-200',
                        };
                    @endphp

                    <tr class="align-top">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <img src="{{ $first->product?->coverUrl() }}" alt="{{ $first->product?->name }}" class="w-14 h-14 rounded-lg bg-zinc-100 object-cover" />
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-zinc-900 truncate">{{ $first->product?->name ?? '—' }}</div>
                                    @if ($displayItems->count() > 1)
                                        <div class="text-xs text-zinc-500">+{{ $displayItems->count() - 1 }} more</div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="text-sm font-mono text-zinc-900">{{ Str::limit($order->public_id, 12, '…') }}</div>
                                <button
                                    type="button"
                                    data-copy-order-id="{{ $order->public_id }}"
                                    title="Copy order ID"
                                    class="grid h-7 w-7 place-items-center rounded-lg border border-zinc-200 bg-white text-zinc-500 transition hover:border-zinc-300 hover:text-zinc-700"
                                >
                                    <span class="copy-icon grid place-items-center">
                                        <x-heroicon-o-clipboard-document class="size-4" aria-hidden="true" />
                                    </span>
                                    <span class="check-icon hidden grid place-items-center">
                                        <x-heroicon-s-check class="size-4" aria-hidden="true" />
                                    </span>
                                    <span class="sr-only">Copy order ID</span>
                                </button>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $badgeClasses }}">
                                {{ method_exists($order->status, 'getLabel') ? $order->status->getLabel() : ucfirst($order->status->value ?? $order->status) }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-sm text-zinc-500">{{ $seats }} {{ Str::plural('seat', $seats) }}</td>

                        <td class="px-6 py-4 text-sm font-semibold text-zinc-900">{{ number_format((float) $order->amount) }} {{ $order->currency }}</td>

                        <td class="px-6 py-4 text-sm text-zinc-500">{{ $order->created_at->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<script>
    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-copy-order-id]');
        if (!button) {
            return;
        }

        event.preventDefault();

        const orderId = button.dataset.copyOrderId;
        if (!orderId) {
            return;
        }

        navigator.clipboard.writeText(orderId).then(function () {
            const copyIcon = button.querySelector('.copy-icon');
            const checkIcon = button.querySelector('.check-icon');

            if (!copyIcon || !checkIcon) {
                return;
            }

            copyIcon.classList.add('hidden');
            checkIcon.classList.remove('hidden');
            button.classList.remove('bg-white', 'text-zinc-500');
            button.classList.add('border-teal-200', 'bg-teal-50', 'text-teal-700');

            clearTimeout(button.__copyResetTimeout);
            button.__copyResetTimeout = setTimeout(function () {
                copyIcon.classList.remove('hidden');
                checkIcon.classList.add('hidden');
                button.classList.remove('border-teal-200', 'bg-teal-50', 'text-teal-700');
                button.classList.add('bg-white', 'text-zinc-500');
            }, 2000);
        });
    });
</script>
