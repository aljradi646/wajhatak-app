<x-admin.layouts.admin heading="المراقبة والتشخيص" title="المساعد الذكي — المراقبة والتشخيص" :breadcrumbs="[['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'المساعد الذكي', 'url' => route('admin.ai.index')]]">

    <div class="space-y-5">
        @include('admin.ai._nav')

        @if (session('status'))
            <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-500/10 dark:border-green-500/30 px-4 py-3 text-sm font-bold text-green-700 dark:text-green-400">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-500/10 dark:border-red-500/30 px-4 py-3 text-sm font-bold text-red-700 dark:text-red-400">
                {{ session('error') }}
            </div>
        @endif

        <x-admin.card title="حالة المخطط والفهرس" description="تشخيص حقيقي من قاعدة البيانات — لا شعارات.">
            <div class="space-y-4 text-sm">
                <div class="flex flex-wrap items-center gap-4">
                    @if ($health->healthy)
                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 font-bold bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400">● جاهز</span>
                    @else
                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 font-bold bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400">● يحتاج إصلاحًا</span>
                    @endif
                    <span>المحرك: <b>حتمي داخل الخادم (deterministic)</b></span>
                    <span>الردود: <b>من عقارات وجهتك الحقيقية فقط</b></span>
                </div>

                @if ($health->message)
                    <p class="text-gray-500 dark:text-gray-400">{{ $health->message }}</p>
                @endif

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach (($health->details['tables'] ?? []) as $table => $info)
                        @php
                            $ok = ! empty($info['exists']) && empty($info['missing_columns']) && empty($info['error']);
                        @endphp
                        <div class="rounded-xl border p-3 {{ $ok ? 'border-green-200 dark:border-green-500/30' : 'border-red-300 dark:border-red-500/40' }}">
                            <div class="font-bold text-xs" dir="ltr">{{ $table }}</div>
                            @if ($ok)
                                <div class="text-xs mt-1 text-green-600 dark:text-green-400">✓ جاهز</div>
                            @else
                                <div class="text-xs mt-1 text-red-600 dark:text-red-400">✗ ناقص</div>
                                @if (! empty($info['missing_columns']))
                                    <div class="text-[10px] text-red-500 mt-1" dir="ltr">{{ implode(', ', $info['missing_columns']) }}</div>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <span>عقارات منشورة في القاعدة: <b>{{ $health->details['published_properties'] ?? 0 }}</b></span>
                    <span>صفوف فهرس المساعد: <b>{{ $health->details['indexed_properties'] ?? 0 }}</b></span>
                </div>

                <form method="POST" action="{{ route('admin.ai.repair') }}">
                    @csrf
                    <x-admin.button type="submit" variant="secondary">تشخيص وإصلاح المخطط تلقائيًا</x-admin.button>
                </form>
            </div>
        </x-admin.card>

        <x-admin.card title="فهم المحرك للعربية (فحص حي)" description="نتيجة تحليل النية لعبِارات حقيقية من داخل نظام التشغيل نفسه — للتأكد من أن الفهم صحيح، لا تخميني.">
            <div class="space-y-2 text-sm">
                @forelse (($rules ?? []) as $probe => $filters)
                    <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 dark:border-gray-700 pb-2">
                        <span class="font-bold">«{{ $probe }}»</span>
                        <code class="text-xs text-gray-500 break-all" dir="ltr">{{ json_encode($filters, JSON_UNESCAPED_UNICODE) }}</code>
                    </div>
                @empty
                    <p class="text-gray-400">لا توجد بيانات فحص.</p>
                @endforelse
            </div>
        </x-admin.card>

        <x-admin.card title="مزامنة فهرس البحث" description="الفهرس يتحدث تلقائيًا عند كل تغيير في العقارات. هذا الزر لإعادة البناء اليدوي.">
            <div class="flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('admin.ai.reindex') }}">
                    @csrf
                    <x-admin.button type="submit" variant="secondary">إعادة بناء الفهرس الآن</x-admin.button>
                </form>
                <a href="{{ route('admin.ai.playground') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold text-white" style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);">
                    <x-admin.icon name="chat" class="h-4 w-4" /> تجربة المحادثة الآن
                </a>
            </div>
        </x-admin.card>
    </div>
</x-admin.layouts.admin>
