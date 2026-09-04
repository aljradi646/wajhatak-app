<x-admin.layouts.admin heading="مستخدم جديد" title="مستخدم جديد">
    <x-admin.card title="إنشاء مستخدم" description="أضف مستخدمًا جديدًا إلى النظام">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            @include('admin.users._form', ['mode' => 'create', 'user' => new \App\Models\User, 'roles' => $roles])
            <div class="mt-6 flex items-center gap-3">
                <x-admin.button type="submit">حفظ المستخدم</x-admin.button>
                <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
            </div>
        </form>
    </x-admin.card>
</x-admin.layouts.admin>
