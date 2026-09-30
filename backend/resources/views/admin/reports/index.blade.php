<x-admin.layouts.admin heading="التقارير" title="التقارير" :breadcrumbs="[['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')]]">

    <div class="space-y-6">
        {{-- سجل التقارير --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">ولّد تقريرًا جديدًا من البيانات الحقيقية، أو تصفّح التقارير التي وُلّدت سابقًا.</p>
            <a href="{{ route('admin.reports.logs') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                <x-admin.icon name="reports" class="h-4 w-4" />
                سجل التقارير
            </a>
        </div>

        {{-- Overview --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <x-admin.card>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">الوكلاء</div>
                        <div class="text-2xl font-extrabold text-gray-900 dark:text-gray-100">{{ number_format($totals['agents']) }}</div>
                    </div>
                    <span class="text-xs font-semibold text-green-600 dark:text-green-400">{{ number_format($totals['agents_active']) }} نشط</span>
                </div>
            </x-admin.card>
            <x-admin.card>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">العقارات</div>
                        <div class="text-2xl font-extrabold text-gray-900 dark:text-gray-100">{{ number_format($totals['properties']) }}</div>
                    </div>
                    <span class="text-xs font-semibold text-green-600 dark:text-green-400">{{ number_format($totals['properties_published']) }} منشور</span>
                </div>
            </x-admin.card>
            <x-admin.card>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">طلبات المعاينة</div>
                        <div class="text-2xl font-extrabold text-gray-900 dark:text-gray-100">{{ number_format($totals['requests']) }}</div>
                    </div>
                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">{{ number_format($totals['requests_pending']) }} بانتظار</span>
                </div>
            </x-admin.card>
            <x-admin.card>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">المستخدمون</div>
                        <div class="text-2xl font-extrabold text-gray-900 dark:text-gray-100">{{ number_format($totals['users']) }}</div>
                    </div>
                    <span class="text-xs font-semibold text-green-600 dark:text-green-400">{{ number_format($totals['users_active']) }} نشط</span>
                </div>
            </x-admin.card>
        </div>

        {{-- Report launchers --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- Agents report --}}
            <x-admin.card title="تقرير الوكلاء" description="جميع الوكلاء مع تقييماتهم وعدد عقاراتهم وحالتهم.">
                <form method="GET" action="{{ route('admin.reports.show', ['type' => 'agents']) }}" class="mt-3 flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">حالة الوكيل</label>
                        <select name="status" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-wajhatak-400 focus:ring-2 focus:ring-wajhatak-300/50 dark:bg-gray-800 dark:border-gray-600">
                            <option value="">الكل</option>
                            <option value="active">نشط</option>
                            <option value="inactive">موقوف</option>
                        </select>
                    </div>
                    <x-admin.button type="submit">عرض التقرير</x-admin.button>
                    <a href="{{ route('admin.reports.show', ['type' => 'agents', 'format' => 'pdf']) }}" class="text-sm font-semibold text-wajhatak-600 hover:text-wajhatak-700 px-2 py-2">PDF مباشر</a>
                </form>
            </x-admin.card>

            {{-- Properties report --}}
            <x-admin.card title="تقرير العقارات" description="كل أنواع العقارات: فلل، شقق، أدوار... مع أسعارها وحالتها ووكلائها.">
                <form method="GET" action="{{ route('admin.reports.show', ['type' => 'properties']) }}" class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">نوع العقار</label>
                        <select name="type" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-wajhatak-400 focus:ring-2 focus:ring-wajhatak-300/50 dark:bg-gray-800 dark:border-gray-600">
                            <option value="">جميع الأنواع</option>
                            @foreach($propertyTypes as $pt)
                                <option value="{{ $pt->slug }}">{{ $pt->name_ar }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">الحالة</label>
                        <select name="status" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-wajhatak-400 focus:ring-2 focus:ring-wajhatak-300/50 dark:bg-gray-800 dark:border-gray-600">
                            <option value="">الكل</option>
                            <option value="published">منشور</option>
                            <option value="pending">قيد المراجعة</option>
                            <option value="draft">مسودة</option>
                            <option value="rejected">مرفوض</option>
                            <option value="archived">مؤرشف</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <x-admin.button type="submit">عرض</x-admin.button>
                        <a href="{{ route('admin.reports.show', ['type' => 'properties', 'format' => 'pdf']) }}" title="تصدير مباشر PDF" class="text-wajhatak-600 hover:text-wajhatak-700 py-2 px-1 text-sm font-semibold">PDF</a>
                    </div>
                </form>
            </x-admin.card>

            {{-- Viewing requests report --}}
            <x-admin.card title="تقرير طلبات المعاينة" description="طلبات العملاء لمعاينة العقارات مع مواعيدها وحالتها.">
                <form method="GET" action="{{ route('admin.reports.show', ['type' => 'requests']) }}" class="mt-3 flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">حالة الطلب</label>
                        <select name="status" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-wajhatak-400 focus:ring-2 focus:ring-wajhatak-300/50 dark:bg-gray-800 dark:border-gray-600">
                            <option value="">الكل</option>
                            <option value="pending">قيد الانتظار</option>
                            <option value="confirmed">مؤكد</option>
                            <option value="rejected">مرفوض</option>
                            <option value="cancelled">ملغي</option>
                            <option value="completed">مكتمل</option>
                        </select>
                    </div>
                    <x-admin.button type="submit">عرض التقرير</x-admin.button>
                    <a href="{{ route('admin.reports.show', ['type' => 'requests', 'format' => 'pdf']) }}" class="text-sm font-semibold text-wajhatak-600 hover:text-wajhatak-700 px-2 py-2">PDF مباشر</a>
                </form>
            </x-admin.card>

            {{-- Users report --}}
            <x-admin.card title="تقرير المستخدمين" description="مشرفون ووكلاء وعملاء مع أدوارهم وحالة حساباتهم.">
                <form method="GET" action="{{ route('admin.reports.show', ['type' => 'users']) }}" class="mt-3 flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">الدور</label>
                        <select name="role" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-wajhatak-400 focus:ring-2 focus:ring-wajhatak-300/50 dark:bg-gray-800 dark:border-gray-600">
                            <option value="">الكل</option>
                            <option value="admin">مشرف</option>
                            <option value="agent">وكيل</option>
                            <option value="user">عميل</option>
                        </select>
                    </div>
                    <x-admin.button type="submit">عرض التقرير</x-admin.button>
                    <a href="{{ route('admin.reports.show', ['type' => 'users', 'format' => 'pdf']) }}" class="text-sm font-semibold text-wajhatak-600 hover:text-wajhatak-700 px-2 py-2">PDF مباشر</a>
                </form>
            </x-admin.card>
        </div>

        {{-- أحدث التقارير المُولَّدة --}}
        @if ($recentLogs->isNotEmpty())
            <x-admin.card :padding="false" title="أحدث التقارير المُولَّدة">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">التقرير</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الصيغة</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">أنشأه</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">التاريخ</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($recentLogs as $log)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">{{ $log->typeLabel() }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $log->formatLabel() }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $log->user?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $log->created_at->translatedFormat('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.reports.logs.download', $log) }}" class="font-bold text-wajhatak-600 hover:text-wajhatak-700">إعادة التوليد</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        @endif
    </div>
</x-admin.layouts.admin>