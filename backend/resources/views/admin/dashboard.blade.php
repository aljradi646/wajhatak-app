<x-admin.layouts.admin heading="لوحة التحكم" title="لوحة التحكم">

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
        <x-admin.card>
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <div class="text-sm text-gray-500">إجمالي المستخدمين</div>
                    <div class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">{{ number_format($totalUsers) }}</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-wajhatak-100 text-wajhatak-600 dark:bg-wajhatak-500/10 dark:text-wajhatak-300">
                    <x-admin.icon name="users" class="h-6 w-6" />
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700 pt-2">الحسابات النشطة وغير النشطة</div>
        </x-admin.card>

        <x-admin.card>
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <div class="text-sm text-gray-500">الوكلاء النشطون</div>
                    <div class="text-3xl font-extrabold text-green-600 dark:text-green-400">{{ number_format($activeAgents) }}</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-100 text-green-600 dark:bg-green-500/10 dark:text-green-400">
                    <x-admin.icon name="agents" class="h-6 w-6" />
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700 pt-2">وكلاء معتمدون على المنصة</div>
        </x-admin.card>

        <x-admin.card>
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <div class="text-sm text-gray-500">العقارات المنشورة</div>
                    <div class="text-3xl font-extrabold text-green-600 dark:text-green-400">{{ number_format($publishedProperties) }}</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <x-admin.icon name="properties" class="h-6 w-6" />
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700 pt-2">المعروضة للعامة الآن</div>
        </x-admin.card>

        <x-admin.card>
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <div class="text-sm text-gray-500">عقارات بانتظار المراجعة</div>
                    <div class="text-3xl font-extrabold text-amber-600 dark:text-amber-400">{{ number_format($pendingProperties) }}</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-wajhatak-100 text-wajhatak-600 dark:bg-wajhatak-500/10 dark:text-wajhatak-300">
                    <x-admin.icon name="activity" class="h-6 w-6" />
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700 pt-2">تحتاج قرار إدارة</div>
        </x-admin.card>

        <x-admin.card>
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <div class="text-sm text-gray-500">طلبات معاينة معلقة</div>
                    <div class="text-3xl font-extrabold text-red-600 dark:text-red-400">{{ number_format($pendingViewingRequests) }}</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                    <x-admin.icon name="viewing-requests" class="h-6 w-6" />
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700 pt-2">طلبات تتطلب متابعة الوكلاء</div>
        </x-admin.card>
    </div>

    {{-- Communication + engagement row --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-4">
        <x-admin.card>
            <div class="flex items-center gap-4">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400">
                    <x-admin.icon name="messages" class="h-5 w-5" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">المحادثات</div>
                    <div class="text-2xl font-extrabold text-gray-900 dark:text-gray-100">{{ number_format($totalConversations) }}</div>
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">محادثات عملاء ↔ وكلاء نشطة</div>
        </x-admin.card>

        <x-admin.card>
            <div class="flex items-center gap-4">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                    <x-admin.icon name="messages" class="h-5 w-5" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">إجمالي الرسائل</div>
                    <div class="text-2xl font-extrabold text-gray-900 dark:text-gray-100">{{ number_format($totalMessages) }}</div>
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ number_format($messagesLast7Days) }} رسالة خلال آخر 7 أيام</div>
        </x-admin.card>

        <x-admin.card>
            <div class="flex items-center gap-4">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">
                    <x-admin.icon name="properties" class="h-5 w-5" />
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">المفضلات</div>
                    <div class="text-2xl font-extrabold text-gray-900 dark:text-gray-100">{{ number_format($favoritesCount) }}</div>
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">عقارات أضافها المستخدمون إلى المفضلة</div>
        </x-admin.card>

        <x-admin.card>
            <div class="space-y-2">
                <div class="text-sm font-bold text-gray-700 dark:text-gray-200">توزيع حالات العقارات</div>
                @php
                    $statusLabels = ['published' => 'منشور', 'pending' => 'بانتظار المراجعة', 'draft' => 'مسودة', 'rejected' => 'مرفوض', 'archived' => 'مؤرشف'];
                    $statusColors = ['published' => 'bg-green-500', 'pending' => 'bg-amber-500', 'draft' => 'bg-gray-400', 'rejected' => 'bg-red-500', 'archived' => 'bg-indigo-400'];
                    $statusMax = max(1, max($propertiesByStatus));
                @endphp
                @foreach ($statusLabels as $status => $label)
                    <div class="flex items-center gap-2">
                        <span class="w-28 text-xs text-gray-500 dark:text-gray-400 truncate">{{ $label }}</span>
                        <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                            <div class="h-full rounded-full {{ $statusColors[$status] }}" style="width: {{ (int) round($propertiesByStatus[$status] / $statusMax * 100) }}%"></div>
                        </div>
                        <span class="w-8 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">{{ $propertiesByStatus[$status] }}</span>
                    </div>
                @endforeach
            </div>
        </x-admin.card>
    </div>

    {{-- Latest properties + recent activity --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-4">
        <x-admin.card>
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-gray-900 dark:text-gray-100">أحدث العقارات</h3>
                <a href="{{ route('admin.properties.index') }}" class="text-sm text-wajhatak-600 hover:text-wajhatak-700 dark:text-wajhatak-300 font-bold">عرض الكل</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($latestProperties as $property)
                    <div class="flex items-center gap-3 py-2.5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-wajhatak-100 text-wajhatak-600 dark:bg-wajhatak-500/10 dark:text-wajhatak-300">
                            <x-admin.icon name="properties" class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.properties.show', $property) }}" class="block truncate text-sm font-bold text-gray-800 dark:text-gray-100 hover:text-wajhatak-600">{{ $property->title }}</a>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $property->type?->name_ar }} • {{ $property->agent?->user?->name ?? '—' }}</div>
                        </div>
                        <div class="text-sm font-extrabold text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ number_format((float) $property->price) }} {{ $property->currency }}</div>
                    </div>
                @empty
                    <div class="py-8 text-center text-sm text-gray-400">لا توجد عقارات بعد</div>
                @endforelse
            </div>
        </x-admin.card>

        <x-admin.card>
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-gray-900 dark:text-gray-100">آخر النشاطات</h3>
                <a href="{{ route('admin.activity-logs.index') }}" class="text-sm text-wajhatak-600 hover:text-wajhatak-700 dark:text-wajhatak-300 font-bold">سجل النظام</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($recentActivity as $log)
                    <div class="flex items-center gap-3 py-2.5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                            <x-admin.icon name="activity" class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm text-gray-800 dark:text-gray-100">{{ $log->description }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $log->user?->name ?? 'النظام' }} • {{ $log->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-sm text-gray-400">لا يوجد نشاط مسجّل بعد</div>
                @endforelse
            </div>
        </x-admin.card>
    </div>
</x-admin.layouts.admin>
