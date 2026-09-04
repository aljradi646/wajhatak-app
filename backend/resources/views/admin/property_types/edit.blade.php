<x-admin.layouts.admin heading="تعديل نوع العقار" title="تعديل نوع العقار">
    <x-admin.card title="تعديل نوع عقار" description="{{ $propertyType->name_ar }}">
        <form method="POST" action="{{ route('admin.property-types.update', $propertyType) }}">
            @csrf
            @method('PATCH')
            @include('admin.property_types._form', ['propertyType' => $propertyType])
            <div class="mt-6 flex items-center gap-3">
                <x-admin.button type="submit">حفظ التعديلات</x-admin.button>
                <a href="{{ route('admin.property-types.index') }}" class="text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
            </div>
        </form>
    </x-admin.card>
</x-admin.layouts.admin>
