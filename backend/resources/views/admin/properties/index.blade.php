<x-admin.layouts.admin heading="العقارات" title="العقارات">
    <div class="mb-4 flex flex-col lg:flex-row lg:items-center gap-3 lg:justify-between">
        <form method="GET" class="flex flex-wrap flex-1 gap-2">
            <div class="sm:w-64">
                <x-admin.input name="search" value="{{ $search }}" placeholder="بحث بالعنوان أو المرجع..." />
            </div>
            <select name="status" class="rounded-lg border-gray-300 shadow-sm text-sm">
                <option value="">كل الحالات</option>
                @foreach(['draft','pending','published','rejected','archived'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>
                        {{ match($s) { 'draft'=>'مسودة', 'pending'=>'قيد المراجعة', 'published'=>'منشور', 'rejected'=>'مرفوض', 'archived'=>'مؤرشف' } }}
                    </option>
                @endforeach
            </select>
            <select name="transaction" class="rounded-lg border-gray-300 shadow-sm text-sm">
                <option value="">الكل</option>
                <option value="sale" @selected(request('transaction') === 'sale')>بيع</option>
                <option value="rent" @selected(request('transaction') === 'rent')>إيجار</option>
            </select>
            <x-admin.button variant="secondary" type="submit">تصفية</x-admin.button>
            <a href="{{ route('admin.properties.index') }}" class="inline-flex items-center px-3 py-2 text-sm text-gray-500 hover:text-gray-700">مسح</a>
        </form>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.properties.trash') }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold bg-white text-gray-700 ring-1 ring-gray-300 rounded-lg hover:bg-gray-50">
                سلة المحذوفات
            </a>
            <a href="{{ route('admin.properties.create') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-bold text-white rounded-xl hover:shadow-lg" style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E); box-shadow: 0 4px 12px rgba(14, 138, 109, 0.25);">
                + عقار جديد
            </a>
        </div>
    </div>

    <x-admin.card :padding="false">
        <form method="POST" action="{{ route('admin.properties.bulk') }}" data-bulk-form>
            @csrf
            <input type="hidden" name="action" value="">
            <div class="flex items-center gap-2 px-4 py-2 border-b border-gray-100 bg-gray-50">
                <span class="text-sm text-gray-600">تحديد الكل:</span>
                <input type="checkbox" data-select-all class="h-4 w-4 rounded border-gray-300 text-wajhatak-600">
                <button type="button" data-bulk-action="delete" onclick="submitBulk('delete')" class="ms-2 text-sm text-red-600 hover:text-red-800 font-medium">حذف</button>
                <button type="button" data-bulk-action="force" onclick="submitBulk('force')" class="text-sm text-red-700 hover:text-red-900 font-medium">حذف نهائي</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 w-8"></th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">العنوان</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">الوكيل</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">النوع</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">المعاملة</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">الحالة</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">السعر</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">الغرف</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">تاريخ النشر</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($properties as $property)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="ids[]" value="{{ $property->id }}" class="bulk-item h-4 w-4 rounded border-gray-300 text-wajhatak-600">
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $property->title }}</div>
                                    <div class="text-xs text-gray-500">{{ $property->reference_code }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $property->agent?->user?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $property->type?->name_ar ?? $property->property_type_id }}</td>
                                <td class="px-4 py-3"><x-admin.badge :status="$property->transaction_type->value" /></td>
                                <td class="px-4 py-3"><x-admin.badge :status="$property->status->value" /></td>
                                <td class="px-4 py-3 text-gray-600">{{ number_format((float) $property->price, 2) }} {{ $property->currency }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $property->bedrooms ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $property->published_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.properties.show', $property) }}" class="text-wajhatak-600 hover:text-wajhatak-700 text-sm font-medium">عرض</a>
                                        <a href="{{ route('admin.properties.edit', $property) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">تعديل</a>
                                        <form method="POST" action="{{ route('admin.properties.destroy', $property) }}" onsubmit="return confirm('نقل هذا العقار إلى سلة المحذوفات؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500">لا توجد عقارات.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.pagination', ['paginator' => $properties])
        </form>
    </x-admin.card>

    @push('scripts')
    <script>
        document.querySelectorAll('[data-select-all]').forEach(function (box) {
            box.addEventListener('change', function () {
                document.querySelectorAll('.bulk-item').forEach(function (i) { i.checked = box.checked; });
            });
        });
        window.submitBulk = function (action) {
            const form = document.querySelector('[data-bulk-form]');
            if (!form.querySelector('.bulk-item:checked')) { alert('حدد عقارًا واحدًا على الأقل.'); return; }
            if (!confirm('تأكيد الإجراء المحدد على العقارات المحددة؟')) return;
            form.querySelector('input[name="action"]').value = action;
            form.submit();
        };
    </script>
    @endpush
</x-admin.layouts.admin>
