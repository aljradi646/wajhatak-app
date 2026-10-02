<x-admin.layouts.admin heading="المساعد الذكي AI Agent" title="المساعد الذكي" :breadcrumbs="[['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')]]">

    <div class="space-y-5">
        @include('admin.ai._nav')

        @if (session('status'))
            <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-500/10 dark:border-green-500/30 px-4 py-3 text-sm font-bold text-green-700 dark:text-green-400">
                {{ session('status') }}
            </div>
        @endif

        {{-- حالة المحرك --}}
        <x-admin.card title="حالة AI Agent Orchestrator" description="منظومة وكيل ذكاء اصطناعي حقيقي تدعم استدعاء الأدوات والتنفيذ المباشر ومراعاة الصلاحيات والتأكيد الصارم للبيانات.">
            <div class="flex flex-wrap items-center gap-4 text-sm">
                @if ($enabled && $health->healthy)
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">● AI Agent جاهز</span>
                @elseif (! $enabled)
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 font-bold bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">● معطّل من الإعدادات</span>
                @else
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 font-bold bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400">● يحتاج إصلاحًا</span>
                @endif

                <span>الاسم: <b>{{ $assistantName }}</b></span>
                @if ($health->message)
                    <span class="text-gray-500 dark:text-gray-400">{{ $health->message }}</span>
                @endif

                <div class="ms-auto flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.ai.monitoring') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        <x-admin.icon name="activity" class="h-4 w-4" /> المراقبة والتشخيص
                    </a>
                    <a href="{{ route('admin.ai.playground') }}" class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-bold text-white shadow-sm" style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);">
                        <x-admin.icon name="chat" class="h-4 w-4" /> تجربة المحادثة المباشرة
                    </a>
                </div>
            </div>
        </x-admin.card>

        {{-- إحصاءات سريعة --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach ([
                'إجمالي المحادثات' => $stats['total_conversations'],
                'إجمالي الرسائل' => $stats['total_messages'],
                'طلبات ناجحة' => $stats['successful_requests'],
                'طلبات اليوم' => $stats['requests_today'],
            ] as $label => $value)
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-2xs">
                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="text-2xl font-black text-gray-900 dark:text-gray-100 mt-1">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        {{-- أقسام الإعدادات --}}
        <div>
            <h2 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-3">أقسام الإعدادات</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($sections as $key => $definition)
                    <a href="{{ route('admin.ai.settings', ['section' => $key]) }}"
                       class="group block rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 transition hover:border-wajhatak-300 hover:shadow-md">
                        <div class="flex items-center gap-2">
                            <x-admin.icon name="settings" class="h-4 w-4 text-wajhatak-600" />
                            <span class="font-bold text-gray-900 dark:text-gray-100">{{ $definition['label'] }}</span>
                        </div>
                        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">{{ $definition['description'] }}</p>
                        <span class="mt-3 inline-block text-xs font-bold text-wajhatak-600 group-hover:underline">فتح القسم ←</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-admin.layouts.admin>
