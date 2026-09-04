<x-admin.layouts.admin heading="وكيل جديد" title="وكيل جديد">
    <x-admin.card title="إنشاء وكيل" description="أضف وكيلًا جديدًا إلى المنصة">
        <form method="POST" action="{{ route('admin.agents.store') }}">
            @csrf
            @include('admin.agents._form', ['mode' => 'create', 'agent' => new \App\Models\Agent, 'users' => $users])
            <div class="mt-6 flex items-center gap-3">
                <x-admin.button type="submit">حفظ الوكيل</x-admin.button>
                <a href="{{ route('admin.agents.index') }}" class="text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
            </div>
        </form>
    </x-admin.card>
</x-admin.layouts.admin>
