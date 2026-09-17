<x-filament-panels::page>
    {{ $this->table }}

    @if (filled($this->keygenError))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900" role="alert">
            {{ $this->keygenError }}
        </div>
    @endif
</x-filament-panels::page>
