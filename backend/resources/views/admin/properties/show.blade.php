<x-admin.layouts.admin heading="تفاصيل العقار" title="تفاصيل العقار">
    <div class="mb-4 flex items-center gap-2">
        <a href="{{ route('admin.properties.edit', $property) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700">تعديل</a>
        <a href="{{ route('admin.properties.index') }}" class="inline-flex items-center px-3 py-1.5 text-sm font-semibold bg-white text-gray-700 ring-1 ring-gray-300 rounded-lg hover:bg-gray-50">رجوع للقائمة</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-admin.card title="معلومات العقار">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">العنوان</dt><dd class="font-medium text-left">{{ $property->title }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الرمز المرجعي</dt><dd class="font-medium">{{ $property->reference_code }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الرابط المختصر</dt><dd class="font-medium text-left" dir="ltr">{{ $property->slug }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">نوع العقار</dt><dd class="font-medium">{{ $property->type?->name_ar ?? '—' }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الموقع</dt><dd class="font-medium">{{ $property->location?->city }}{{ $property->location?->district ? ' - '.$property->location->district : '' }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">المعاملة</dt><dd><x-admin.badge :status="$property->transaction_type->value" /></dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الحالة</dt><dd><x-admin.badge :status="$property->status->value" /></dd></div>
            </dl>
        </x-admin.card>

        <x-admin.card title="التفاصيل المالية والفيزيائية">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">السعر</dt><dd class="font-medium">{{ number_format((float) $property->price, 2) }} {{ $property->currency }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">المساحة</dt><dd class="font-medium">{{ $property->area ? $property->area.' م²' : '—' }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الغرف</dt><dd class="font-medium">{{ $property->bedrooms ?? '—' }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">الحمامات</dt><dd class="font-medium">{{ $property->bathrooms ?? '—' }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">مواقف السيارات</dt><dd class="font-medium">{{ $property->parking_spaces ?? '—' }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">مفروش / جديد / مميز</dt>
                    <dd>
                        <span class="text-gray-700">{{ $property->is_furnished ? '✓ مفروش' : '—' }}</span>
                        <span class="text-gray-700 ms-2">{{ $property->is_new ? '✓ جديد' : '' }}</span>
                        <span class="text-wajhatak-600 ms-2">{{ $property->is_featured ? '★ مميز' : '' }}</span>
                    </dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">تاريخ النشر</dt><dd class="font-medium">{{ $property->published_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
                <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">تاريخ الإنشاء</dt><dd class="font-medium">{{ $property->created_at?->format('Y-m-d H:i') }}</dd></div>
            </dl>
        </x-admin.card>
    </div>

    <div class="mt-4">
        <x-admin.card title="الوصف">
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $property->description ?: '—' }}</p>
        </x-admin.card>
    </div>

    <div class="mt-4">
        <x-admin.card title="الخصائص ({{ $property->features->count() }})">
            @if($property->features->isEmpty())
                <p class="text-sm text-gray-400">لا توجد خصائص.</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach($property->features as $feature)
                        <span class="inline-flex items-center rounded-full bg-wajhatak-50 text-wajhatak-700 px-3 py-1 text-xs font-medium">{{ $feature->name_ar }}</span>
                    @endforeach
                </div>
            @endif
        </x-admin.card>
    </div>

    <div class="mt-4">
        <x-admin.card title="الصور ({{ $property->images->count() }})">
            @if($property->images->isEmpty())
                <p class="text-sm text-gray-400">لا توجد صور.</p>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach($property->images as $image)
                        <div class="group relative aspect-video overflow-hidden rounded-lg bg-gray-100">
                            @if($image->is_primary)
                                <span class="absolute top-2 right-2 z-10 rounded px-2 py-0.5 text-[10px] font-bold text-white" style="background: linear-gradient(135deg, #075E4A, #0E8A6D);">غلاف</span>
                            @endif
                            <img src="{{ $image->image_url }}" alt="{{ $property->title }}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                    @endforeach
                </div>
            @endif
        </x-admin.card>
    </div>
</x-admin.layouts.admin>
