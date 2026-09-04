<x-admin.layouts.admin heading="سلة المحذوفات" title="سلة المحذوفات">
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
        <div>
            <p class="text-sm text-gray-500">العقارات المحذوفة مؤقتًا. يمكنك استعادتها أو حذفها نهائيًا.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.properties.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold bg-white text-gray-700 ring-1 ring-gray-300 rounded-lg hover:bg-gray-50">› العودة للعقارات</a>
        </div>
    </div>

    <x-admin.card :padding="false">
        <form method="POST" action="{{ route('admin.properties.bulk') }}" data-bulk-form>
            @csrf
            <input type="hidden" name="action" value="">
            <div class="flex items-center gap-2 px-4 py-2 border-b border-gray-100 bg-gray-50">
                <span class="text-sm text-gray-600">تحديد الكل:</span>
                <input type="checkbox" data-select-all class="h-4 w-4 rounded border-gray-300 text-wajhatak-600">
                <button type="button" onclick="submitBulk('restore')" class="ms-2 text-sm text-green-600 hover:text-green-800 font-medium">استعادة</button>
                <button type="button" onclick="submitBulk('force')" class="text-sm text-red-700 hover:text-red-900 font-medium">حذف نهائي</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 w-8"></th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">العنوان</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">المرجع</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">الحالة</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">تاريخ الحذف</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($properties as $property)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="ids[]" value="{{ $property->id }}" class="bulk-item h-4 w-4 rounded border-gray-300 text-wajhatak-600">
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $property->title }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $property->reference_code }}</td>
                                <td class="px-4 py-3"><x-admin.badge :status="$property->status->value" /></td>
                                <td class="px-4 py-3 text-gray-600">{{ $property->deleted_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="{{ route('admin.properties.restore', $property) }}">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-800 text-sm font-medium">استعادة</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.properties.force-delete', $property) }}" onsubmit="return confirm('حذف نهائي لا يمكن التراجع عنه؟ ستحذف كل البيانات المرتبطة.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">حذف نهائي</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">سلة المحذوفات فارغة.</td>
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
            form.querySelector('input[name="action"]').value = action;
            form.submit();
        };
    </script>
    @endpush
</x-admin.layouts.admin>
