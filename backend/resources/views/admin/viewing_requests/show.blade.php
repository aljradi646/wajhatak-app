<x-admin.layouts.admin heading="تفاصيل طلب المعاينة" title="تفاصيل طلب المعاينة">
    <div class="mb-4 flex items-center gap-2">
        <a href="{{ route('admin.viewing-requests.edit', $viewingRequest) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700">تعديل</a>
        <a href="{{ route('admin.viewing-requests.index') }}" class="inline-flex items-center px-3 py-1.5 text-sm font-semibold bg-white text-gray-700 ring-1 ring-gray-300 rounded-lg hover:bg-gray-50">رجوع للقائمة</a>
    </div>

    <x-admin.card>
        <dl class="space-y-3 text-sm">
            <div class="flex flex-wrap justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">العقار</dt>
                <dd class="font-medium text-left">{{ $viewingRequest->property?->title ?? '—' }}</dd></div>
            <div class="flex flex-wrap justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">العميل</dt>
                <dd class="font-medium text-left">{{ $viewingRequest->client?->name ?? '—' }}</dd></div>
            <div class="flex flex-wrap justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الوكيل</dt>
                <dd class="font-medium text-left">{{ $viewingRequest->agent?->user?->name ?? ('وكيل #'.$viewingRequest->agent_id) }}</dd></div>
            <div class="flex flex-wrap justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">التاريخ</dt>
                <dd class="font-medium">{{ $viewingRequest->scheduled_date?->format('Y-m-d') }}</dd></div>
            <div class="flex flex-wrap justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الوقت</dt>
                <dd class="font-medium">{{ $viewingRequest->scheduled_time }}</dd></div>
            <div class="flex flex-wrap justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الحالة</dt>
                <dd><x-admin.badge :status="$viewingRequest->status->value" /></dd></div>
            <div class="flex flex-wrap justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">تاريخ الإنشاء</dt>
                <dd class="font-medium">{{ $viewingRequest->created_at?->format('Y-m-d H:i') }}</dd></div>
        </dl>
        @if($viewingRequest->notes)
            <div class="mt-4 rounded-lg bg-gray-50 p-4">
                <p class="text-sm font-medium text-gray-700 mb-1">الملاحظات</p>
                <p class="text-sm text-gray-600 whitespace-pre-line">{{ $viewingRequest->notes }}</p>
            </div>
        @endif
    </x-admin.card>
</x-admin.layouts.admin>
