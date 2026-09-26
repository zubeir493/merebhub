<x-filament-widgets::widget>
    <div class="relative isolate overflow-hidden rounded-3xl bg-gray-950 px-6 py-8 shadow-sm ring-1 ring-white/10 sm:px-8 lg:px-10">
        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold tracking-wide text-indigo-300">Merchant workspace</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl">
                    {{ $this->getGreeting() }}, {{ $user?->name ?? 'there' }}.
                </h2>
                <p class="mt-3 max-w-xl text-sm leading-6 text-gray-300 sm:text-base">
                    Keep your catalog moving, follow sales, and stay close to your customers from one place.
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap gap-3">
                <a
                    href="{{ \App\Filament\Merchant\Resources\Products\ProductResource::getUrl('create', panel: 'merchant') }}"
                    class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-sm transition hover:bg-gray-100"
                >
                    Add product
                </a>
                <a
                    href="{{ \App\Filament\Merchant\Resources\Orders\OrderResource::getUrl('index', panel: 'merchant') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-white/20 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10"
                >
                    View sales
                </a>
            </div>
        </div>

        <div class="pointer-events-none absolute -right-16 -top-24 h-72 w-72 rounded-full bg-indigo-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 right-1/3 h-64 w-64 rounded-full bg-cyan-400/10 blur-3xl"></div>
    </div>
</x-filament-widgets::widget>
