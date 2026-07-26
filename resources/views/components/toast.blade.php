@props([
    'message',
    'type' => 'success',
    'duration' => 5000,
])

@php
    $isError = $type === 'error';
@endphp

<div
    x-data="{ visible: true }"
    x-init="window.setTimeout(() => visible = false, {{ (int) $duration }})"
    x-show="visible"
    x-transition:enter="transition-[opacity,transform] duration-150 ease-out"
    x-transition:enter-start="translate-y-1 opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition-[opacity,transform] duration-100 ease-out"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="-translate-y-1 opacity-0"
    @keydown.escape.window="visible = false"
    role="{{ $isError ? 'alert' : 'status' }}"
    aria-live="{{ $isError ? 'assertive' : 'polite' }}"
    {{ $attributes->class([
        'pointer-events-auto flex items-center gap-3 rounded-xl px-3 py-2.5 shadow-[0_1px_2px_oklch(0_0_0/0.08),0_12px_32px_oklch(0_0_0/0.14)] ring-1',
        'bg-white text-teal-950 ring-teal-700/15' => ! $isError,
        'bg-white text-rose-950 ring-rose-700/15' => $isError,
    ]) }}
>
    <span @class([
        'mt-0.5 grid size-10 shrink-0 place-items-center rounded-lg',
        'bg-teal-50 text-teal-700' => ! $isError,
        'bg-rose-50 text-rose-700' => $isError,
    ])>
        @if ($isError)
            <x-heroicon-o-exclamation-circle class="size-5" />
        @else
            <x-heroicon-o-check-circle class="size-5" />
        @endif
    </span>
    <p class="min-w-0 flex-1 py-1 text-sm font-semibold leading-5 text-pretty">{{ $message }}</p>
    <button
        type="button"
        @click="visible = false"
        class="grid size-10 shrink-0 place-items-center rounded-lg text-zinc-400 transition-[color,background-color,transform] hover:bg-zinc-100 hover:text-zinc-700 active:scale-[0.96]"
        aria-label="Dismiss notification"
    >
        <x-heroicon-o-x-mark class="size-5" />
    </button>
</div>
