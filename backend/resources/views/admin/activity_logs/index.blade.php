<x-admin.layouts.admin heading="سجل النشاطات" title="سجل النشاطات" :breadcrumbs="[['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')]]">
    @php
        $logLabels = [
            'user' => 'مستخدم',
            'agent' => 'وكيل',
            'property' => 'عقار',
            'viewing_request' => 'طلب معاينة',
            'property_type' => 'نوع عقار',
            'property_feature' => 'خاصية عقار',
            'setting' => 'إعداد',
        ];
        $labelFor = fn ($name) => $logLabels[$name] ?? $name;
    @endphp

    <div class="mb-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
        <form method="GET" class="flex flex-wrap gap-2">
            <x-admin.input name="search" value="{{ $search }}" placeholder="بحث في الوصف..." />
            <select name="log_name" class="rounded-lg border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600">
                <option value="">كل الأنواع</option>
                @foreach($logNames as $name)
                    <option value="{{ $name }}" @selected($filters['log_name'] === $name)>{{ $labelFor($name) }}</option>
                @endforeach
            </select>
            <x-admin.button variant="secondary" type="submit">تصفية</x-admin.button>
            @if($search || $filters['log_name'] || $filters['user_id'])
                <a href="{{ route('admin.activity-logs.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700">مسح</a>
            @endif
        </form>
    </div>

    <x-admin.card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">الوصف</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">المستخدم</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">النوع</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">عنوان IP</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">التاريخ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $log->description }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                {{ $log->user?->name ?? '—' }}
                                <span class="block text-xs text-gray-400" dir="ltr">{{ $log->user?->email ?? '' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-700 px-2 py-0.5 text-xs font-semibold dark:bg-amber-900/40 dark:text-amber-300">{{ $labelFor($log->log_name) }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-left" dir="ltr">{{ $log->ip_address ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $log->created_at->translatedFormat('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">لا توجد سجلات نشاط.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials.pagination', ['paginator' => $logs])
    </x-admin.card>
</x-admin.layouts.admin>
