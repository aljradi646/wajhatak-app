<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <x-admin.input label="الاسم الكامل" name="name" value="{{ $user->name ?? '' }}" required />
    <x-admin.input label="البريد الإلكتروني" name="email" type="email" value="{{ $user->email ?? '' }}" required />
    <x-admin.input label="رقم الجوال" name="phone" value="{{ $user->phone ?? '' }}" />
    <div>
        <label for="locale" class="block text-sm font-medium text-gray-700 mb-1">اللغة <span class="text-red-500">*</span></label>
        <select id="locale" name="locale" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-wajhatak-400 focus:ring-wajhatak-300 text-sm">
            <option value="ar" @selected((old('locale', $user->locale ?? 'ar')) === 'ar')>العربية</option>
            <option value="en" @selected((old('locale', $user->locale ?? 'ar')) === 'en')>English</option>
        </select>
    </div>
    <x-admin.input label="كلمة المرور" name="password" type="password" {{ ($mode ?? 'create') === 'create' ? 'required' : '' }} help="{{ ($mode ?? 'create') === 'create' ? '' : 'اتركها فارغة للإبقاء على كلمة المرور الحالية' }}" />
    <x-admin.input label="مسار الصورة الرمزية (اختياري)" name="avatar_path" value="{{ $user->avatar_path ?? '' }}" />
    <x-admin.checkbox label="حساب نشط" name="is_active" :checked="old('is_active', $user->is_active ?? true)" />
</div>

<div class="mt-5">
    <span class="block text-sm font-medium text-gray-700 mb-1">الأدوار</span>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        @foreach($roles as $roleName)
            <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <input type="checkbox" name="roles[]" value="{{ $roleName }}" class="h-4 w-4 rounded border-gray-300 text-wajhatak-600 focus:ring-wajhatak-300"
                    @checked(in_array($roleName, old('roles', $user->roles->pluck('name')->toArray() ?? []), true))>
                {{ $roleName }}
            </label>
        @endforeach
    </div>
</div>
