<x-admin.layouts.admin heading="تعديل الوكيل" title="تعديل الوكيل">
    <x-admin.card title="تعديل وكيل" description="{{ $agent->user->name }}">
        <form method="POST" action="{{ route('admin.agents.update', $agent) }}">
            @csrf
            @method('PATCH')
            @include('admin.agents._form', ['mode' => 'edit', 'agent' => $agent, 'users' => $users])
            <div class="mt-6 flex items-center gap-3">
                <x-admin.button type="submit">حفظ التعديلات</x-admin.button>
                <a href="{{ route('admin.agents.index') }}" class="text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
            </div>
        </form>
    </x-admin.card>
</x-admin.layouts.admin>
