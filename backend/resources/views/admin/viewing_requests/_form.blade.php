<div>
    <label for="property_id" class="block text-sm font-medium text-gray-700 mb-1">العقار <span class="text-red-500">*</span></label>
    <select id="property_id" name="property_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-wajhatak-400 focus:ring-wajhatak-300 text-sm">
        <option value="">— اختر العقار —</option>
        @foreach($properties as $property)
            <option value="{{ $property->id }}" @selected((int) old('property_id', $viewingRequest->property_id ?? 0) === (int) $property->id)>
                {{ $property->title }} ({{ $property->reference_code }})
            </option>
        @endforeach
    </select>
    @error('property_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5">
    <div>
        <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">العميل <span class="text-red-500">*</span></label>
        <select id="client_id" name="client_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-wajhatak-400 focus:ring-wajhatak-300 text-sm">
            <option value="">— اختر العميل —</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" @selected((int) old('client_id', $viewingRequest->client_id ?? 0) === (int) $client->id)>
                    {{ $client->name }} ({{ $client->email }})
                </option>
            @endforeach
        </select>
        @error('client_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="agent_id" class="block text-sm font-medium text-gray-700 mb-1">الوكيل <span class="text-red-500">*</span></label>
        <select id="agent_id" name="agent_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-wajhatak-400 focus:ring-wajhatak-300 text-sm">
            <option value="">— اختر الوكيل —</option>
            @foreach($agents as $agent)
                <option value="{{ $agent->id }}" @selected((int) old('agent_id', $viewingRequest->agent_id ?? 0) === (int) $agent->id)>
                    {{ $agent->user?->name ?? ('وكيل #'.$agent->id) }}
                </option>
            @endforeach
        </select>
        @error('agent_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5">
    <x-admin.input label="تاريخ المعاينة" name="scheduled_date" type="date" value="{{ old('scheduled_date', $viewingRequest->scheduled_date?->format('Y-m-d') ?? '') }}" required />
    <x-admin.input label="وقت المعاينة" name="scheduled_time" type="time" value="{{ old('scheduled_time', $viewingRequest->scheduled_time ?? '') }}" required />
    <div>
        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">الحالة <span class="text-red-500">*</span></label>
        <select id="status" name="status" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-wajhatak-400 focus:ring-wajhatak-300 text-sm">
            @foreach($statuses as $s)
                <option value="{{ $s->value }}" @selected(old('status', $viewingRequest->status?->value ?? 'pending') === $s->value)>
                    {{ match($s->value) { 'pending'=>'قيد الانتظار', 'confirmed'=>'مؤكد', 'rejected'=>'مرفوض', 'cancelled'=>'ملغي', 'completed'=>'مكتمل' } }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-5">
    <x-admin.textarea label="ملاحظات" name="notes">{{ $viewingRequest->notes ?? '' }}</x-admin.textarea>
</div>
