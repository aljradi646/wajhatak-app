{{--
    شجرة تنقّل المساعد الذكي — تُضمَّن في كل صفحات المساعد حتى تبقى كل الأقسام
    في مكان واحد مرتب، بدل حشرها في صفحة واحدة ضخمة.
--}}
@php
    $currentSection = request()->route('section');

    $aiNav = [
        ['label' => 'نظرة عامة', 'icon' => 'ai-assistant', 'route' => 'admin.ai.index', 'params' => [], 'section' => null],
    ];

    foreach ($sections as $key => $definition) {
        $aiNav[] = [
            'label' => $definition['label'],
            'icon' => 'settings',
            'route' => 'admin.ai.settings',
            'params' => ['section' => $key],
            'section' => $key,
        ];
    }

    $aiNav[] = ['label' => 'المراقبة والتشخيص', 'icon' => 'activity', 'route' => 'admin.ai.monitoring', 'params' => [], 'section' => null];
    $aiNav[] = ['label' => 'الإحصاءات', 'icon' => 'reports', 'route' => 'admin.ai.stats', 'params' => [], 'section' => null];
    $aiNav[] = ['label' => 'السجلات', 'icon' => 'clock', 'route' => 'admin.ai.logs', 'params' => [], 'section' => null];
    $aiNav[] = ['label' => 'تجربة المحادثة', 'icon' => 'chat', 'route' => 'admin.ai.playground', 'params' => [], 'section' => null];
@endphp

<nav class="flex flex-wrap items-center gap-2" aria-label="أقسام المساعد الذكي">
    @foreach ($aiNav as $item)
        @php
            $isActive = request()->routeIs($item['route']) && $item['section'] === $currentSection;
        @endphp
        <a href="{{ route($item['route'], $item['params']) }}"
           class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-bold transition
                {{ $isActive
                    ? 'text-white shadow-sm'
                    : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700' }}"
           @if ($isActive) style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);" @endif>
            <x-admin.icon :name="$item['icon']" class="h-4 w-4" />
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
