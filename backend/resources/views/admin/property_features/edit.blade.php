<x-admin.layouts.admin heading="تعديل الخاصية" title="تعديل الخاصية">
    <x-admin.card title="تعديل خاصية" description="{{ $propertyFeature->name_ar }}">
        <form method="POST" action="{{ route('admin.property-features.update', $propertyFeature) }}">
            @csrf
            @method('PATCH')
            @include('admin.property_features._form', ['propertyFeature' => $propertyFeature])
            <div class="mt-6 flex items-center gap-3">
                <x-admin.button type="submit">حفظ التعديلات</x-admin.button>
                <a href="{{ route('admin.property-features.index') }}" class="text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
            </div>
        </form>
    </x-admin.card>
</x-admin.layouts.admin>
