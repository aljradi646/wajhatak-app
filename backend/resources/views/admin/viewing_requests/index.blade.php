<x-admin.layouts.admin heading="طلبات المعاينة" title="طلبات المعاينة">
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
        <form method="GET" class="flex gap-2 sm:w-96">
            <x-admin.input name="search" value="{{ $search }}" placeholder="بحث بالعقار أو العميل..." />
            <select name="status" class="rounded-lg border-gray-300 shadow-sm text-sm">
                <option value="">كل الحالات</option>
                @foreach(['pending','confirmed','rejected','cancelled','completed'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>
                        {{ match($s) { 'pending'=>'قيد الانتظار', 'confirmed'=>'مؤكد', 'rejected'=>'مرفوض', 'cancelled'=>'ملغي', 'completed'=>'مكتمل' } }}
                    </option>
                @endforeach
            </select>
            <x-admin.button variant="secondary" type="submit">تصفية</x-admin.button>
        </form>
        <a href="{{ route('admin.viewing-requests.create') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-bold text-white rounded-xl hover:shadow-lg" style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E); box-shadow: 0 4px 12px rgba(14, 138, 109, 0.25);">
            + طلب معاينة
        </a>
    </div>

    <x-admin.card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">العقار</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">العميل</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">الوكيل</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">التاريخ</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">الوقت</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">الحالة</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($viewingRequests as $request)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $request->property?->title ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $request->client?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $request->agent?->user?->name ?? ('وكيل #'.$request->agent_id) }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $request->scheduled_date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $request->scheduled_time }}</td>
                            <td class="px-4 py-3"><x-admin.badge :status="$request->status->value" /></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.viewing-requests.show', $request) }}" class="text-wajhatak-600 hover:text-wajhatak-700 text-sm font-medium">عرض</a>
                                    <a href="{{ route('admin.viewing-requests.edit', $request) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">تعديل</a>
                                    <form method="POST" action="{{ route('admin.viewing-requests.destroy', $request) }}" onsubmit="return confirm('حذف طلب المعاينة؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">لا توجد طلبات معاينة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials.pagination', ['paginator' => $viewingRequests])
    </x-admin.card>
</x-admin.layouts.admin>
