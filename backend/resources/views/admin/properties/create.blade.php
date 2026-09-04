<x-admin.layouts.admin heading="عقار جديد" title="عقار جديد">
    <x-admin.card title="إنشاء عقار" description="أضف عقارًا جديدًا إلى المنصة">
        <form method="POST" action="{{ route('admin.properties.store') }}">
            @csrf
            @include('admin.properties._form', ['property' => new \App\Models\Property])
            <div class="mt-6 flex items-center gap-3">
                <x-admin.button type="submit">حفظ العقار</x-admin.button>
                <a href="{{ route('admin.properties.index') }}" class="text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
            </div>
        </form>
    </x-admin.card>
</x-admin.layouts.admin>
