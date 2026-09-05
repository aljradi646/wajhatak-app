<x-admin.layouts.admin heading="العقارات غير المعتمدة" title="العقارات غير المعتمدة" :breadcrumbs="[['label' => 'العقارات', 'url' => route('admin.properties.index')]]">

    <div class="mb-4 flex flex-col lg:flex-row lg:items-center gap-3 lg:justify-between">
        <form method="GET" class="flex flex-wrap flex-1 gap-2">
            <div class="sm:w-64">
                <x-admin.input name="search" value="{{ $search }}" placeholder="بحث بالعنوان أو المرجع..." />
            </div>
            <select name="status" class="rounded-lg border-gray-300 shadow-sm text-sm">
                <option value="pending" @selected($status === 'pending')>قيد المراجعة ({{ $counts['pending'] }})</option>
                <option value="rejected" @selected($status === 'rejected')>مرفوض ({{ $counts['rejected'] }})</option>
                <option value="draft" @selected($status === 'draft')>مسودة ({{ $counts['draft'] }})</option>
            </select>
            <select name="transaction" class="rounded-lg border-gray-300 shadow-sm text-sm">
                <option value="">الكل</option>
                <option value="sale" @selected(request('transaction') === 'sale')>بيع</option>
                <option value="rent" @selected(request('transaction') === 'rent')>إيجار</option>
            </select>
            <x-admin.button variant="secondary" type="submit">تصفية</x-admin.button>
            <a href="{{ route('admin.properties.pending') }}" class="inline-flex items-center px-3 py-2 text-sm text-gray-500 hover:text-gray-700">مسح</a>
        </form>
        <a href="{{ route('admin.properties.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold bg-white text-gray-700 ring-1 ring-gray-300 rounded-lg hover:bg-gray-50">
            <x-admin.icon name="back" class="h-4 w-4" />
            العقارات المنشورة
        </a>
    </div>

    <x-admin.card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">العنوان</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الوكيل</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">النوع</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">المعاملة</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الحالة</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">السعر</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">آخر تحديث</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($properties as $property)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $property->title }}</div>
                                <div class="text-xs text-gray-500">{{ $property->reference_code }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $property->agent?->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $property->type?->name_ar ?? $property->property_type_id }}</td>
                            <td class="px-4 py-3"><x-admin.badge :status="$property->transaction_type->value" /></td>
                            <td class="px-4 py-3"><x-admin.badge :status="$property->status->value" /></td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ number_format((float) $property->price, 2) }} {{ $property->currency }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $property->updated_at?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <form method="POST" action="{{ route('admin.properties.approve', $property) }}" onsubmit="return confirm('اعتماد هذا العقار ونشره للعامة؟');">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-green-700">
                                            <x-admin.icon name="check" class="h-4 w-4" />
                                            اعتماد ونشر
                                        </button>
                                    </form>
                                    @if ($property->status->value !== 'rejected')
                                        <form method="POST" action="{{ route('admin.properties.reject', $property) }}" onsubmit="return confirm('رفض هذا العقار وإرجاعه للوكيل؟');">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600 ring-1 ring-red-200 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/30">
                                                <x-admin.icon name="x-circle" class="h-4 w-4" />
                                                رفض
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('admin.properties.show', $property) }}" class="text-wajhatak-600 hover:text-wajhatak-700 text-sm font-medium">عرض</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">لا توجد عقارات غير معتمدة هنا.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials.pagination', ['paginator' => $properties])
    </x-admin.card>

    <div class="mt-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
        عندما يرفع الوكيل عقارًا جديدًا من تطبيق الجوال يظهر هنا بقسم "قيد المراجعة" حتى تعتمده لنشر، ويمكنك رفضه ليصحّحه الوكيل ويعيد إرساله.
    </div>
</x-admin.layouts.admin>