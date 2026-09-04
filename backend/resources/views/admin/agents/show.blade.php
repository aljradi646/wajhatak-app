<x-admin.layouts.admin heading="تفاصيل الوكيل" title="تفاصيل الوكيل">
    <div class="mb-4 flex items-center gap-2">
        <a href="{{ route('admin.agents.edit', $agent) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700">تعديل</a>
        <a href="{{ route('admin.agents.index') }}" class="inline-flex items-center px-3 py-1.5 text-sm font-semibold bg-white text-gray-700 ring-1 ring-gray-300 rounded-lg hover:bg-gray-50">رجوع للقائمة</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-admin.card title="معلومات الوكيل">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الوكيل</dt><dd class="font-medium">{{ $agent->user->name }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">البريد الإلكتروني</dt><dd class="font-medium">{{ $agent->user->email }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">رقم الترخيص</dt><dd class="font-medium">{{ $agent->license_number ?? '—' }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">التقييم</dt><dd class="font-medium text-amber-400">★ {{ number_format((float) $agent->rating, 2) }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">عدد المراجعات</dt><dd class="font-medium">{{ number_format($agent->reviews_count) }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الحالة</dt>
                    <dd>@if($agent->is_active)<span class="text-green-600 font-semibold">نشط</span>@else<span class="text-gray-500 font-semibold">غير نشط</span>@endif</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">تاريخ الإنشاء</dt><dd class="font-medium">{{ $agent->created_at?->format('Y-m-d H:i') }}</dd></div>
            </dl>
        </x-admin.card>
        <x-admin.card title="النبذة">
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $agent->bio ?: '—' }}</p>
        </x-admin.card>
    </div>

    <div class="mt-4">
        <x-admin.card title="عقارات الوكيل ({{ $agent->properties->count() }})">
            @if($agent->properties->isEmpty())
                <p class="text-sm text-gray-400">لا توجد عقارات.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-right font-semibold text-gray-600">العنوان</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-600">الحالة</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-600">السعر</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($agent->properties as $property)
                                <tr>
                                    <td class="px-4 py-2">
                                        <a href="{{ route('admin.properties.show', $property) }}" class="text-wajhatak-600 hover:text-wajhatak-700 font-medium">{{ $property->title }}</a>
                                    </td>
                                    <td class="px-4 py-2"><x-admin.badge :status="$property->status->value" /></td>
                                    <td class="px-4 py-2 text-gray-600">{{ number_format((float) $property->price, 2) }} {{ $property->currency }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-admin.card>
    </div>
</x-admin.layouts.admin>
