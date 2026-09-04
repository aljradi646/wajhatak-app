@props(['label' => null, 'name' => null, 'required' => false, 'help' => null, 'options' => [], 'selected' => null, 'placeholder' => '— اختر —'])

<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-bold text-gray-700 mb-1 dark:text-gray-300">
            {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'w-full rounded-xl border-gray-200 shadow-sm focus:border-wajhatak-400 focus:ring-wajhatak-300 text-sm bg-gray-50 focus:bg-white transition dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100']) }}
        style="border-width: 1.5px;"
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($options as $value => $text)
            <option value="{{ $value }}" @if((string) old($name, $selected ?? '') === (string) $value) selected @endif>{{ $text }}</option>
        @endforeach
    </select>
    @if($help)<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $help }}</p>@endif
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
