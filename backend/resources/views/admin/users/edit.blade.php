<x-admin.layouts.admin heading="تعديل المستخدم" title="تعديل المستخدم">
    <x-admin.card title="تعديل مستخدم" description="{{ $user->name }}">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PATCH')
            @include('admin.users._form', ['mode' => 'edit', 'user' => $user, 'roles' => $roles])
            <div class="mt-6 flex items-center gap-3">
                <x-admin.button type="submit">حفظ التعديلات</x-admin.button>
                <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
            </div>
        </form>
    </x-admin.card>
</x-admin.layouts.admin>
