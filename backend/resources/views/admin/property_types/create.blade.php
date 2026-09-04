<x-admin.layouts.admin heading="نوع عقار جديد" title="نوع عقار جديد">
    <x-admin.card title="إنشاء نوع عقار" description="أضف نوع عقار جديدًا">
        <form method="POST" action="{{ route('admin.property-types.store') }}">
            @csrf
            @include('admin.property_types._form', ['propertyType' => new \App\Models\PropertyType])
            <div class="mt-6 flex items-center gap-3">
                <x-admin.button type="submit">حفظ النوع</x-admin.button>
                <a href="{{ route('admin.property-types.index') }}" class="text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
            </div>
        </form>
    </x-admin.card>
</x-admin.layouts.admin>
