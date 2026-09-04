<x-admin.layouts.admin heading="خاصية جديدة" title="خاصية جديدة">
    <x-admin.card title="إنشاء خاصية" description="أضف خاصية عقار جديدة">
        <form method="POST" action="{{ route('admin.property-features.store') }}">
            @csrf
            @include('admin.property_features._form', ['propertyFeature' => new \App\Models\PropertyFeature])
            <div class="mt-6 flex items-center gap-3">
                <x-admin.button type="submit">حفظ الخاصية</x-admin.button>
                <a href="{{ route('admin.property-features.index') }}" class="text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
            </div>
        </form>
    </x-admin.card>
</x-admin.layouts.admin>
