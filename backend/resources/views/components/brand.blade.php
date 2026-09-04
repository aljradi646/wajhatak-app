@props([
    'size' => 'md',
    'showTagline' => true,
    'class' => '',
])

@php
    $mark = match ($size) {
        'sm' => 'h-8 w-8 text-lg rounded-lg',
        'lg' => 'h-14 w-14 text-3xl rounded-2xl',
        default => 'h-10 w-10 text-xl rounded-xl',
    };
    $en = match ($size) {
        'sm' => 'text-[10px] tracking-[0.18em]',
        'lg' => 'text-sm tracking-[0.25em]',
        default => 'text-[11px] tracking-[0.2em]',
    };
    $ar = match ($size) {
        'sm' => 'text-lg',
        'lg' => 'text-2xl',
        default => 'text-xl',
    };
@endphp

<div class="flex items-center gap-3 {{ $class }}">
    <span class="flex {{ $mark }} shrink-0 items-center justify-center font-black text-white" style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);">
        و
    </span>
    <div class="leading-tight">
        <div class="font-extrabold tracking-[0.2em] {{ $en }}" style="color: #B97D1B;">WAJHATAK</div>
        <div class="font-extrabold text-gray-900 dark:text-gray-100 leading-tight {{ $ar }}">وجهتك</div>
        @if ($showTagline)
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 leading-snug">وجهتك إلى العقار المناسب.</p>
        @endif
    </div>
</div>
