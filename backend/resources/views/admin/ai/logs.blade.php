<x-admin.layouts.admin heading="سجلات المساعد" title="المساعد الذكي — السجلات" :breadcrumbs="[['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'المساعد الذكي', 'url' => route('admin.ai.index')]]">

    <div class="space-y-5">
        @include('admin.ai._nav')

        <div class="flex flex-wrap items-center justify-between gap-3">
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <select name="status" class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-600">
                    <option value="">كل الحالات</option>
                    @foreach (['ok' => 'ناجح', 'blocked' => 'محجوب', 'error' => 'خطأ', 'fallback' => 'بديل'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-admin.button variant="secondary" type="submit">تصفية</x-admin.button>
                @if ($status)
                    <a href="{{ route('admin.ai.logs') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-700 px-2 dark:text-gray-400">مسح</a>
                @endif
            </form>
            <a href="{{ route('admin.ai.stats') }}" class="text-sm font-bold text-wajhatak-600 hover:text-wajhatak-700">الإحصاءات ←</a>
        </div>

        <x-admin.card :padding="false" title="سجل طلبات المساعد" description="بيانات تشغيلية آمنة — لا تُسجل أسرار ولا محتوى رسائل المستخدمين الحرة.">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الوقت</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">النية</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">المعايير</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">النتائج</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الحالة</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">رمز الخطأ</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الزمن</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">البحث</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $log->created_at?->format('m/d H:i') }}</td>
                                <td class="px-4 py-3 font-bold">{{ $log->intent ?? '—' }}</td>
                                <td class="px-4 py-3 max-w-xs truncate text-xs text-gray-500" dir="ltr">{{ $log->structured_filters ? json_encode($log->structured_filters, JSON_UNESCAPED_UNICODE) : '—' }}</td>
                                <td class="px-4 py-3">{{ $log->results_count }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $colors = [
                                            'ok' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
                                            'blocked' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                                            'error' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
                                            'fallback' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                        ];
                                    @endphp
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $colors[$log->status] ?? 'bg-gray-100' }}">{{ $log->status }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-red-500" dir="ltr">{{ $log->error_code ?? '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $log->latency_ms }}ms</td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $log->search_ms }}ms</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-12 text-center text-gray-400">لا توجد طلبات مسجلة بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.pagination', ['paginator' => $logs])
        </x-admin.card>
    </div>
</x-admin.layouts.admin>
