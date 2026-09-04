@props(['active' => false, 'href' => '#'])

@php
    $classes = $active
        ? 'bg-wajhatak-50 text-wajhatak-700 font-semibold dark:bg-wajhatak-500/10 dark:text-wajhatak-300'
        : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition ' . $classes]) }} :class="collapsed ? 'justify-center px-2' : ''">
    @isset($icon)
        <span class="shrink-0">{{ $icon }}</span>
    @endisset
    <span x-show="!collapsed">{{ $slot }}</span>
</a>
