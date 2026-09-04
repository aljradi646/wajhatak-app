<x-admin.layouts.admin heading="تفاصيل المستخدم" title="تفاصيل المستخدم">
    <div class="mb-4 flex items-center gap-2">
        <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700">تعديل</a>
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-3 py-1.5 text-sm font-semibold bg-white text-gray-700 ring-1 ring-gray-300 rounded-lg hover:bg-gray-50">رجوع للقائمة</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-admin.card title="معلومات الحساب">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الاسم</dt><dd class="font-medium">{{ $user->name }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">البريد الإلكتروني</dt><dd class="font-medium">{{ $user->email }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الجوال</dt><dd class="font-medium">{{ $user->phone ?? '—' }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">اللغة</dt><dd class="font-medium">{{ $user->locale }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">تم التحقق من البريد</dt><dd class="font-medium">{{ $user->email_verified_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الصورة الرمزية</dt><dd class="font-medium">{{ $user->avatar_path ?? '—' }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الحالة</dt>
                    <dd>@if($user->is_active)<span class="text-green-600 font-semibold">نشط</span>@else<span class="text-gray-500 font-semibold">غير نشط</span>@endif</dd></div>
            </dl>
        </x-admin.card>

        <x-admin.card title="الأدوار والصلاحيات">
            <div class="flex flex-wrap gap-2">
                @forelse($user->roles as $role)
                    <span class="inline-flex items-center rounded-full bg-blue-100 text-blue-700 px-3 py-1 text-sm font-semibold">{{ $role->name }}</span>
                @empty
                    <span class="text-gray-400 text-sm">لا أدوار</span>
                @endforelse
            </div>
            @if($user->agentProfile)
                <div class="mt-5 pt-4 border-t border-gray-100">
                    <h3 class="text-sm font-bold text-gray-900 mb-2">بيانات الوكيل المرتبط</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">رقم الترخيص</dt><dd>{{ $user->agentProfile->license_number ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">التقييم</dt><dd>{{ $user->agentProfile->rating ?? '0' }}</dd></div>
                    </dl>
                    <a href="{{ route('admin.agents.show', $user->agentProfile) }}" class="mt-3 inline-block text-wajhatak-600 hover:text-wajhatak-700 text-sm font-medium">عرض ملف الوكيل ←</a>
                </div>
            @endif
        </x-admin.card>
    </div>
</x-admin.layouts.admin>
