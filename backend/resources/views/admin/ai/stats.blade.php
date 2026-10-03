<x-admin.layouts.admin heading="إحصاءات المساعد" title="المساعد الذكي — الإحصاءات" :breadcrumbs="[['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'المساعد الذكي', 'url' => route('admin.ai.index')]]">

    <div class="space-y-5">
        @include('admin.ai._nav')

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach ([
                'إجمالي المحادثات' => $stats['total_conversations'],
                'إجمالي الرسائل' => $stats['total_messages'],
                'طلبات ناجحة' => $stats['successful_requests'],
                'طلبات محجوبة' => $stats['blocked_requests'],
                'أخطاء' => $stats['errors'],
                'ردود بديلة (fallback)' => $stats['fallback_requests'],
                'متوسط زمن الاستجابة' => $stats['avg_response_ms'].' ms',
                'متوسط زمن البحث' => $stats['avg_search_ms'].' ms',
                'استدعاءات الأدوات' => $stats['tool_calls'],
                'بحث بلا نتائج' => $stats['no_match_searches'],
                'طلبات اليوم' => $stats['requests_today'],
            ] as $label => $value)
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="text-2xl font-black text-gray-900 dark:text-gray-100 mt-1">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <x-admin.card>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.ai.logs') }}" class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-bold text-white" style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);">
                    <x-admin.icon name="clock" class="h-4 w-4" /> سجل الطلبات الكامل
                </a>
                <a href="{{ route('admin.ai.monitoring') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                    <x-admin.icon name="activity" class="h-4 w-4" /> المراقبة والتشخيص
                </a>
            </div>
        </x-admin.card>
    </div>
</x-admin.layouts.admin>
