@props(['label' => null, 'name' => null, 'checked' => false, 'description' => null])

<div class="flex items-start gap-3">
    <input
        id="{{ $name }}"
        type="checkbox"
        name="{{ $name }}"
        value="1"
        @if(old($name, $checked ? 1 : 0)) checked @endif
        {{ $attributes->merge(['class' => 'mt-0.5 h-4 w-4 rounded border-gray-300 text-wajhatak-600 focus:ring-wajhatak-300']) }}
    >
    @if($label)
        <div>
            <label for="{{ $name }}" class="block text-sm font-bold text-gray-700 dark:text-gray-300">{{ $label }}</label>
            @if($description)<p class="text-xs text-gray-500 dark:text-gray-400">{{ $description }}</p>@endif
        </div>
    @endif
</div>
