<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div>
        <label for="agent_id" class="block text-sm font-medium text-gray-700 mb-1">الوكيل <span class="text-red-500">*</span></label>
        <select id="agent_id" name="agent_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-wajhatak-400 focus:ring-wajhatak-300 text-sm">
            <option value="">— اختر وكيلًا —</option>
            @foreach($agents as $agent)
                <option value="{{ $agent->id }}" @selected((int) old('agent_id', $property->agent_id ?? 0) === (int) $agent->id)>
                    {{ $agent->user->name }} ({{ $agent->license_number ?? 'بدون ترخيص' }})
                </option>
            @endforeach
        </select>
        @error('agent_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="property_type_id" class="block text-sm font-medium text-gray-700 mb-1">نوع العقار <span class="text-red-500">*</span></label>
        <select id="property_type_id" name="property_type_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-wajhatak-400 focus:ring-wajhatak-300 text-sm">
            <option value="">— اختر النوع —</option>
            @foreach($types as $type)
                <option value="{{ $type->id }}" @selected((int) old('property_type_id', $property->property_type_id ?? 0) === (int) $type->id)>
                    {{ $type->name_ar }} / {{ $type->name_en }}
                </option>
            @endforeach
        </select>
        @error('property_type_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="property_location_id" class="block text-sm font-medium text-gray-700 mb-1">الموقع <span class="text-red-500">*</span></label>
        <select id="property_location_id" name="property_location_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-wajhatak-400 focus:ring-wajhatak-300 text-sm">
            <option value="">— اختر الموقع —</option>
            @foreach($locations as $location)
                <option value="{{ $location->id }}" @selected((int) old('property_location_id', $property->property_location_id ?? 0) === (int) $location->id)>
                    {{ $location->city }}{{ $location->district ? ' - '.$location->district : '' }}
                </option>
            @endforeach
        </select>
        @error('property_location_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <x-admin.input label="العنوان" name="title" value="{{ $property->title ?? '' }}" required />
    <x-admin.input label="الرابط المختصر (slug)" name="slug" value="{{ $property->slug ?? '' }}" required />
    <x-admin.input label="الرمز المرجعي" name="reference_code" value="{{ $property->reference_code ?? '' }}" required />
</div>

<div class="mt-5">
    <x-admin.textarea label="الوصف" name="description">{{ $property->description ?? '' }}</x-admin.textarea>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5">
    <div>
        <label for="transaction_type" class="block text-sm font-medium text-gray-700 mb-1">نوع المعاملة <span class="text-red-500">*</span></label>
        <select id="transaction_type" name="transaction_type" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-wajhatak-400 focus:ring-wajhatak-300 text-sm">
            @foreach($transactions as $t)
                <option value="{{ $t->value }}" @selected(old('transaction_type', $property->transaction_type?->value ?? '') === $t->value)>
                    {{ $t->value === 'sale' ? 'بيع' : 'إيجار' }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">الحالة <span class="text-red-500">*</span></label>
        <select id="status" name="status" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-wajhatak-400 focus:ring-wajhatak-300 text-sm">
            @foreach($statuses as $s)
                <option value="{{ $s->value }}" @selected(old('status', $property->status?->value ?? 'draft') === $s->value)>
                    {{ match($s->value) { 'draft'=>'مسودة', 'pending'=>'قيد المراجعة', 'published'=>'منشور', 'rejected'=>'مرفوض', 'archived'=>'مؤرشف' } }}
                </option>
            @endforeach
        </select>
    </div>
    <x-admin.input label="العملة" name="currency" value="{{ $property->currency ?? 'SAR' }}" required />
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5">
    <x-admin.input label="السعر" name="price" type="number" step="0.01" min="0" value="{{ $property->price ?? '' }}" required />
    <x-admin.input label="المساحة" name="area" type="number" step="0.01" min="0" value="{{ $property->area ?? '' }}" />
    <x-admin.input label="عدد الغرف" name="bedrooms" type="number" min="0" value="{{ $property->bedrooms ?? '' }}" />
    <x-admin.input label="عدد الحمامات" name="bathrooms" type="number" min="0" value="{{ $property->bathrooms ?? '' }}" />
    <x-admin.input label="مواقف السيارات" name="parking_spaces" type="number" min="0" value="{{ $property->parking_spaces ?? '' }}" />
    <x-admin.input label="تاريخ النشر" name="published_at" type="datetime-local" value="{{ $property->published_at?->format('Y-m-d\TH:i') ?? '' }}" />
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5">
    <x-admin.checkbox label="مفروش" name="is_furnished" :checked="old('is_furnished', $property->is_furnished ?? false)" />
    <x-admin.checkbox label="جديد" name="is_new" :checked="old('is_new', $property->is_new ?? false)" />
    <x-admin.checkbox label="مميز" name="is_featured" :checked="old('is_featured', $property->is_featured ?? false)" />
</div>

<div class="mt-5">
    <span class="block text-sm font-medium text-gray-700 mb-1">الخصائص</span>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
        @foreach($features as $feature)
            <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <input type="checkbox" name="features[]" value="{{ $feature->id }}"
                    class="h-4 w-4 rounded border-gray-300 text-wajhatak-600 focus:ring-wajhatak-300"
                    @checked(in_array($feature->id, old('features', $property->features?->pluck('id')->toArray() ?? []), true))>
                {{ $feature->name_ar }}
            </label>
        @endforeach
    </div>
</div>
