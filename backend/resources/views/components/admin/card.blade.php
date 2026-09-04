@props(['title' => null, 'description' => null, 'padding' => true])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 dark:bg-gray-800 dark:ring-gray-700']) }}>
    @if($title || $description)
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            @if($title)<h2 class="text-base font-bold text-gray-900 dark:text-gray-100">{{ $title }}</h2>@endif
            @if($description)<p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>@endif
        </div>
    @endif
    <div class="{{ $padding ? 'p-5' : '' }}">
        {{ $slot }}
    </div>
</div>
