@props(['label' => null, 'name' => null, 'required' => false, 'help' => null, 'placeholder' => null])

<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-bold text-gray-700 mb-1 dark:text-gray-300">
            {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows ?? 4 }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'w-full rounded-xl border-gray-200 shadow-sm focus:border-wajhatak-400 focus:ring-wajhatak-300 text-sm bg-gray-50 focus:bg-white transition dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100']) }}
        style="border-width: 1.5px;"
    >{{ old($name, $slot) }}</textarea>
    @if($help)<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $help }}</p>@endif
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
