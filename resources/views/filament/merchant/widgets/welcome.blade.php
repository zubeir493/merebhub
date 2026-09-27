<x-filament-widgets::widget>
    <div class="rounded-2xl bg-gray-950 px-5 py-5 shadow-sm ring-1 ring-white/10 sm:px-6">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold tracking-tight text-white sm:text-2xl">
                    {{ $this->getGreeting() }}, {{ $user?->name ?? 'there' }}.
                </h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-6 text-gray-300">
                    Keep your catalog moving, follow sales, and stay close to your customers from one place.
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap gap-2.5">
                <a
                    href="{{ \App\Filament\Merchant\Resources\Products\ProductResource::getUrl('create', panel: 'merchant') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-white px-3.5 py-2 text-sm font-semibold text-gray-950 shadow-sm transition hover:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                >
                    Add product
                </a>
                <a
                    href="{{ \App\Filament\Merchant\Resources\Orders\OrderResource::getUrl('index', panel: 'merchant') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-white/20 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                >
                    View sales
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
