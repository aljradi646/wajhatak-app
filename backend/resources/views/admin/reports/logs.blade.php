<x-admin.layouts.admin heading="سجل التقارير" title="سجل التقارير" :breadcrumbs="[['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'التقارير', 'url' => route('admin.reports.index')]]">

    <div class="space-y-5">
        {{-- إحصاءات السجل --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <x-admin.card>
                <div class="text-xs font-bold text-gray-500 dark:text-gray-400">إجمالي التقارير المُولَّدة</div>
                <div class="mt-1 text-2xl font-extrabold text-gray-900 dark:text-gray-100">{{ number_format($summary['total']) }}</div>
            </x-admin.card>
            @foreach (['pdf' => 'PDF', 'excel' => 'Excel', 'csv' => 'CSV'] as $key => $label)
                <x-admin.card>
                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="mt-1 text-2xl font-extrabold text-gray-900 dark:text-gray-100">{{ number_format((int) ($summary['byFormat'][$key] ?? 0)) }}</div>
                </x-admin.card>
            @endforeach
        </div>

        {{-- الفلترة --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-wajhatak-600 dark:text-gray-400">
                <x-admin.icon name="back" class="h-4 w-4" />
                كل التقارير
            </a>
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <select name="type" class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-600">
                    <option value="">جميع الأنواع</option>
                    @foreach (\App\Models\ReportLog::TYPE_LABELS as $type => $label)
                        <option value="{{ $type }}" @selected($typeFilter === $type)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-admin.button variant="secondary" type="submit">تصفية</x-admin.button>
                @if ($typeFilter)
                    <a href="{{ route('admin.reports.logs') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-700 px-2">مسح</a>
                @endif
            </form>
        </div>

        {{-- الجدول --}}
        <x-admin.card :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300 whitespace-nowrap">التقرير</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300 whitespace-nowrap">الصيغة</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300 whitespace-nowrap">المرشحات</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300 whitespace-nowrap">عدد السجلات</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300 whitespace-nowrap">أنشأه</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300 whitespace-nowrap">التاريخ</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300 whitespace-nowrap">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ $log->typeLabel() }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-full bg-wajhatak-50 px-2.5 py-0.5 text-xs font-bold text-wajhatak-700 dark:bg-wajhatak-500/10 dark:text-wajhatak-300">{{ $log->formatLabel() }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                    @forelse (($log->filters ?? []) as $key => $value)
                                        <span class="inline-block rounded bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-700 ml-1">{{ $key }}: {{ $value }}</span>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums text-gray-600 dark:text-gray-300">{{ number_format($log->row_count) }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $log->user?->name ?? '—' }}
                                    <span class="block text-xs text-gray-400" dir="ltr">{{ $log->user?->email ?? '' }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $log->created_at->translatedFormat('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.reports.logs.download', $log) }}" class="font-bold text-wajhatak-600 hover:text-wajhatak-700">إعادة التوليد</a>
                                        <a href="{{ route('admin.reports.show', array_merge(['type' => $log->type], $log->filters ?? [])) }}" class="font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400">معاينة</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">لم يتم توليد أي تقرير بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.pagination', ['paginator' => $logs])
        </x-admin.card>
    </div>
</x-admin.layouts.admin>
