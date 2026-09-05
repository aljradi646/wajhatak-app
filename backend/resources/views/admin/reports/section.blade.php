<x-admin.layouts.admin :heading="$report['heading']" :title="$report['heading']" :breadcrumbs="[['label' => 'التقارير', 'url' => route('admin.reports.index')]]">

    <div class="space-y-5">
        {{-- Toolbar: export + back --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-wajhatak-600 dark:text-gray-400">
                <x-admin.icon name="back" class="h-4 w-4" />
                كل التقارير
            </a>
            <div class="flex flex-wrap items-center gap-2">
                <button onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <x-admin.icon name="print" class="h-4 w-4" /> طباعة
                </button>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-red-700">
                    <x-admin.icon name="file-pdf" class="h-4 w-4" /> PDF
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'excel']) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-green-700 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-green-800">
                    <x-admin.icon name="file-excel" class="h-4 w-4" /> Excel
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-gray-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-gray-700">
                    <x-admin.icon name="file-csv" class="h-4 w-4" /> CSV
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'json']) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-gray-800 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-gray-900 dark:bg-gray-600 dark:hover:bg-gray-500">
                    <x-admin.icon name="file-json" class="h-4 w-4" /> JSON
                </a>
            </div>
        </div>

        {{-- Report header --}}
        <div class="print:hidden">
            <x-admin.card>
                <div class="flex flex-wrap items-center gap-4">
                    @php($logoPath = public_path('storage/branding/logo.png'))
                    @if (file_exists($logoPath))
                        <img src="{{ asset('storage/branding/logo.png') }}" alt="شعار {{ $report['site']['name'] }}" class="h-14 w-14 rounded-xl object-contain bg-wajhatak-50 p-1 dark:bg-wajhatak-500/10">
                    @endif
                    <div class="flex-1">
                        <h2 class="text-xl font-black text-gray-900 dark:text-gray-100">{{ $report['site']['name'] }} — {{ $report['heading'] }}</h2>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $report['description'] }}</p>
                        <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">تاريخ الإنشاء: {{ $report['generated_at']->translatedFormat('d MMMM Y') }} • الساعة {{ $report['generated_at']->format('H:i') }}</p>
                    </div>
                </div>
                @if (! empty($report['filters']))
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3 dark:border-gray-700">
                        @foreach ($report['filters'] as $filter)
                            <span class="inline-flex items-center gap-1 rounded-full bg-wajhatak-50 px-3 py-1 text-xs font-bold text-wajhatak-700 dark:bg-wajhatak-500/10 dark:text-wajhatak-300">
                                {{ $filter['label'] }}: {{ $filter['value'] }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>
        </div>

        {{-- Summary --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach ($report['summary'] as $item)
                <x-admin.card>
                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $item['label'] }}</div>
                    <div class="mt-1 text-lg font-extrabold text-gray-900 dark:text-gray-100">{{ $item['value'] }}</div>
                </x-admin.card>
            @endforeach
        </div>

        {{-- Data table --}}
        <x-admin.card :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700" id="report-table">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            @foreach ($report['columns'] as $col)
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $col['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($report['rows'] as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                @foreach ($report['columns'] as $col)
                                    <td class="px-4 py-3 @if(in_array($col['type'], ['number','money','rating'], true)) text-center tabular-nums @endif whitespace-nowrap">
                                        @if ($col['type'] === 'badge')
                                            @php
                                                $badgeColors = [
                                                    'green' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
                                                    'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                                                    'red' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
                                                    'gray' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                                    'blue' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400',
                                                ];
                                                $state = $row[$col['key']] ?? null;
                                                $style = $badgeColors[$col['colors'][$state] ?? 'gray'] ?? $badgeColors['gray'];
                                            @endphp
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $style }}">{{ $fmt($col, $state) }}</span>
                                        @else
                                            {{ $fmt($col, $row[$col['key']] ?? null) }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($report['columns']) }}" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">لا توجد بيانات مطابقة لشروط التقرير.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    </div>
</x-admin.layouts.admin>