<x-admin.layouts.admin heading="تعديل العقار" title="تعديل العقار">
    <x-admin.card title="تعديل عقار" description="{{ $property->title }}">
        <form method="POST" action="{{ route('admin.properties.update', $property) }}">
            @csrf
            @method('PATCH')
            @include('admin.properties._form', ['property' => $property])
            <div class="mt-6 flex items-center gap-3">
                <x-admin.button type="submit">حفظ التعديلات</x-admin.button>
                <a href="{{ route('admin.properties.index') }}" class="text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
            </div>
        </form>
    </x-admin.card>
</x-admin.layouts.admin>
