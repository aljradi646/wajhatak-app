<x-admin.layouts.admin :heading="$definition['label']" :title="'المساعد الذكي — '.$definition['label']" :breadcrumbs="[['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'المساعد الذكي', 'url' => route('admin.ai.index')]]">

    <div class="space-y-5">
        @include('admin.ai._nav')

        @if (session('status'))
            <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-500/10 dark:border-green-500/30 px-4 py-3 text-sm font-bold text-green-700 dark:text-green-400">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-500/10 dark:border-red-500/30 px-4 py-3 text-sm font-bold text-red-700 dark:text-red-400">
                <ul class="list-disc ms-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.ai.settings.update', ['section' => $section]) }}" class="space-y-5">
            @csrf

            <x-admin.card :title="$definition['label']" :description="$definition['description']">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($definition['fields'] as $field)
                        @php
                            $key = $field['key'];
                            $type = $field['type'] ?? 'text';
                            $value = $values[$key] ?? null;
                        @endphp

                        @if ($type === 'boolean')
                            <label class="flex items-center gap-3 rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                                <input type="checkbox" name="{{ $key }}" value="1" @checked((bool) $value) class="h-5 w-5 rounded accent-wajhatak-600">
                                <span class="text-sm font-bold">{{ $field['label'] }}</span>
                            </label>
                        @elseif ($type === 'forced')
                            <label class="flex items-center gap-3 rounded-xl border border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/10 p-3">
                                <input type="checkbox" checked disabled class="h-5 w-5 rounded accent-wajhatak-600 opacity-60">
                                <span class="text-sm font-bold opacity-80">{{ $field['label'] }}</span>
                                <span class="ms-auto text-xs font-bold text-green-600 dark:text-green-400">مُفعّل دائمًا</span>
                            </label>
                        @elseif ($type === 'textarea')
                            <div class="md:col-span-2">
                                <x-admin.textarea :label="$field['label']" :name="$key" :value="$value" :rows="$field['rows'] ?? 3" />
                            </div>
                        @elseif ($type === 'select')
                            <div>
                                <label for="{{ $key }}" class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">{{ $field['label'] }}</label>
                                <select id="{{ $key }}" name="{{ $key }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800 dark:border-gray-600">
                                    @foreach ($field['options'] as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                                @error($key)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @else
                            <x-admin.input
                                :label="$field['label']"
                                :name="$key"
                                :type="$type"
                                :value="$value"
                                :min="$field['min'] ?? null"
                                :max="$field['max'] ?? null"
                                :step="$field['step'] ?? null"
                                :required="in_array('required', $field['rules'] ?? [], true)"
                            />
                        @endif
                    @endforeach
                </div>
            </x-admin.card>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.ai.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400">إلغاء</a>
                <x-admin.button type="submit">حفظ «{{ $definition['label'] }}»</x-admin.button>
            </div>
        </form>
    </div>
</x-admin.layouts.admin>
