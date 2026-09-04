<x-admin.layouts.admin heading="خصائص العقارات" title="خصائص العقارات">
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
        <form method="GET" class="flex gap-2 sm:w-80">
            <x-admin.input name="search" value="{{ $search }}" placeholder="بحث بالاسم أو الرابط..." />
            <x-admin.button variant="secondary" type="submit">بحث</x-admin.button>
        </form>
        <a href="{{ route('admin.property-features.create') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-bold text-white rounded-xl hover:shadow-lg" style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E); box-shadow: 0 4px 12px rgba(14, 138, 109, 0.25);">
            + خاصية جديدة
        </a>
    </div>

    <x-admin.card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">الاسم بالعربية</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">الاسم بالإنجليزية</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">slug</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">الأيقونة</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">نشط</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($features as $feature)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $feature->name_ar }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $feature->name_en }}</td>
                            <td class="px-4 py-3 text-gray-500 text-left" dir="ltr">{{ $feature->slug }}</td>
                            <td class="px-4 py-3 text-gray-500 text-left" dir="ltr">{{ $feature->icon ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if($feature->is_active)
                                    <span class="inline-flex items-center rounded-full bg-green-100 text-green-700 px-2 py-0.5 text-xs font-semibold">نشط</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs font-semibold">غير نشط</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.property-features.edit', $feature) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">تعديل</a>
                                    <form method="POST" action="{{ route('admin.property-features.destroy', $feature) }}" onsubmit="return confirm('حذف الخاصية؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">لا توجد خصائص.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials.pagination', ['paginator' => $features])
    </x-admin.card>
</x-admin.layouts.admin>
