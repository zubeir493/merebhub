@props(['variant' => 'light'])

<div {{ $attributes->class([
    'mh-grid-field pointer-events-none overflow-hidden',
    'text-teal-700/25' => $variant === 'light',
    'text-teal-200/20' => $variant === 'dark',
]) }} aria-hidden="true">
    <span class="mh-grid-glow absolute left-[34%] top-[18%] h-[48%] w-[48%] rounded-[2rem]"></span>
    <span class="mh-grid-glow mh-grid-glow-secondary absolute left-[60%] top-[54%] h-[28%] w-[28%] rounded-full"></span>
    <span class="mh-grid-lines absolute inset-[-12%] block"></span>
</div>
