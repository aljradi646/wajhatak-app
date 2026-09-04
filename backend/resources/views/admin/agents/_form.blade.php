<div>
    <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">المستخدم المرتبط <span class="text-red-500">*</span></label>
    <select id="user_id" name="user_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-wajhatak-400 focus:ring-wajhatak-300 text-sm">
        <option value="">— اختر مستخدمًا —</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" @selected((int) old('user_id', $agent->user_id ?? 0) === (int) $user->id)>
                {{ $user->name }} ({{ $user->email }})
            </option>
        @endforeach
    </select>
    @error('user_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5">
    <x-admin.input label="رقم الترخيص" name="license_number" value="{{ $agent->license_number ?? '' }}" />
    <x-admin.input label="التقييم" name="rating" type="number" step="0.01" min="0" max="5" value="{{ $agent->rating ?? 0 }}" required />
    <x-admin.input label="عدد المراجعات" name="reviews_count" type="number" min="0" value="{{ $agent->reviews_count ?? 0 }}" required />
</div>

<div class="mt-5">
    <x-admin.textarea label="نبذة عن الوكيل" name="bio">{{ $agent->bio ?? '' }}</x-admin.textarea>
</div>

<div class="mt-5">
    <x-admin.checkbox label="وكيل نشط" name="is_active" :checked="old('is_active', $agent->is_active ?? true)" />
</div>
