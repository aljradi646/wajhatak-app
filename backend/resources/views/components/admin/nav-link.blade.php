@props(['active' => false, 'href' => '#', 'badge' => null])

@php
    $classes = $active
        ? 'bg-wajhatak-50 text-wajhatak-700 font-semibold dark:bg-wajhatak-500/10 dark:text-wajhatak-300'
        : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition ' . $classes]) }} :class="collapsed ? 'justify-center px-2' : ''">
    @isset($icon)
        <span class="shrink-0">{{ $icon }}</span>
    @endisset
    <span class="flex-1 text-right" x-show="!collapsed">{{ $slot }}</span>
    @if (! is_null($badge) && $badge !== '' && $badge > 0)
        <span x-show="!collapsed" class="shrink-0 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">{{ $badge }}</span>
    @endif
</a>
