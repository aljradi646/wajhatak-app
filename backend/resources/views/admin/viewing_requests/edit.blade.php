<x-admin.layouts.admin heading="تعديل طلب المعاينة" title="تعديل طلب المعاينة">
    <x-admin.card title="تعديل طلب معاينة" description="طلب {{ $viewingRequest->client?->name ?? '' }}">
        <form method="POST" action="{{ route('admin.viewing-requests.update', $viewingRequest) }}">
            @csrf
            @method('PATCH')
            @include('admin.viewing_requests._form', ['viewingRequest' => $viewingRequest])
            <div class="mt-6 flex items-center gap-3">
                <x-admin.button type="submit">حفظ التعديلات</x-admin.button>
                <a href="{{ route('admin.viewing-requests.index') }}" class="text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
            </div>
        </form>
    </x-admin.card>
</x-admin.layouts.admin>
