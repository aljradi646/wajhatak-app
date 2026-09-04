<x-admin.layouts.admin heading="طلب معاينة جديد" title="طلب معاينة جديد">
    <x-admin.card title="إنشاء طلب معاينة" description="أضف طلب معاينة جديدًا">
        <form method="POST" action="{{ route('admin.viewing-requests.store') }}">
            @csrf
            @include('admin.viewing_requests._form', ['viewingRequest' => new \App\Models\ViewingRequest])
            <div class="mt-6 flex items-center gap-3">
                <x-admin.button type="submit">حفظ الطلب</x-admin.button>
                <a href="{{ route('admin.viewing-requests.index') }}" class="text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
            </div>
        </form>
    </x-admin.card>
</x-admin.layouts.admin>
