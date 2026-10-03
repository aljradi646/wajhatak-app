<x-admin.layouts.admin :heading="'مراجعة الوكيل — '.$agent->user->name" title="مراجعة الوكيل" :breadcrumbs="[['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'الوكلاء', 'url' => route('admin.agents.index')]]">

    <div class="space-y-5" x-data="{ tab: 'profile' }">

        {{-- رأس الصفحة --}}
        <x-admin.card>
            <div class="flex flex-wrap items-center gap-4">
                @if ($agent->photo_path)
                    <img src="{{ asset('storage/'.$agent->photo_path) }}" class="h-16 w-16 rounded-2xl object-cover" alt="صورة الوكيل">
                @else
                    <span class="flex h-16 w-16 items-center justify-center rounded-2xl text-2xl font-black text-white brand-mark">{{ mb_substr($agent->user->name, 0, 1) }}</span>
                @endif

                <div class="flex-1 min-w-[200px]">
                    <div class="font-black text-lg text-gray-900 dark:text-gray-100">{{ $agent->user->name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $agent->agency_name ?: 'وكيل عقاري' }} · {{ $agent->job_title ?: 'غير محدد' }}</div>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                        @php
                            $verificationBadges = [
                                'approved' => ['label' => 'موثّق', 'class' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'],
                                'pending' => ['label' => 'بانتظار التوثيق', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400'],
                                'rejected' => ['label' => 'مرفوض التوثيق', 'class' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400'],
                            ];
                            $badge = $verificationBadges[$agent->verification_status] ?? ['label' => $agent->verification_status, 'class' => 'bg-gray-100 text-gray-600'];
                        @endphp
                        <span class="rounded-full px-2.5 py-0.5 font-bold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                        <span class="rounded-full px-2.5 py-0.5 font-bold {{ $agent->is_active ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $agent->is_active ? 'نشط' : 'موقوف' }}
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">★ {{ number_format((float) $agent->rating, 2) }} ({{ number_format($agent->reviews_count) }} مراجعة)</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($agent->verification_status === 'pending')
                        <form method="POST" action="{{ route('admin.agents.approve', $agent) }}">
                            @csrf
                            <x-admin.button type="submit">✓ توثيق الحساب</x-admin.button>
                        </form>
                    @endif
                    <a href="{{ route('admin.agents.edit', $agent) }}" class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">تعديل</a>
                    <a href="{{ route('admin.agents.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-wajhatak-600 dark:text-gray-400">
                        <x-admin.icon name="back" class="h-4 w-4" /> رجوع
                    </a>
                </div>
            </div>

            @if ($agent->verification_status === 'rejected' && $agent->rejection_reason)
                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 dark:bg-red-500/10 dark:border-red-500/30 px-4 py-3 text-sm text-red-700 dark:text-red-400">
                    <span class="font-bold">سبب الرفض المسجّل:</span> {{ $agent->rejection_reason }}
                </div>
            @endif
        </x-admin.card>

        {{-- التبويبات --}}
        <div class="flex flex-wrap items-center gap-2">
            @foreach ([
                'profile' => 'بيانات الوكيل',
                'properties' => 'عقارات الوكيل ('.$agent->properties->count().')',
                'verification' => 'التوثيق ('.$verificationEvents->count().')',
                'reports' => 'سجلات التقارير ('.$reportLogs->count().')',
                'conversations' => 'المحادثات ('.$conversations->count().')',
                'viewings' => 'طلبات المعاينة ('.$viewingRequests->count().')',
                'activity' => 'السجل ('.$activities->count().')',
            ] as $tabKey => $tabLabel)
                <button type="button" @click="tab = '{{ $tabKey }}'"
                        :class="tab === '{{ $tabKey }}' ? 'text-white shadow-sm' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700'"
                        class="rounded-xl px-3.5 py-2 text-sm font-bold transition"
                        @if ($loop->first) style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);" @endif>
                    {{ $tabLabel }}
                </button>
            @endforeach
        </div>

        {{-- 1. بيانات الوكيل --}}
        <div x-show="tab === 'profile'" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <x-admin.card title="البيانات الأساسية">
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        'الاسم' => $agent->user->name,
                        'البريد الإلكتروني' => $agent->user->email,
                        'الجوال' => $agent->phone ?? $agent->user->phone,
                        'واتساب' => $agent->whatsapp,
                        'المكتب' => $agent->agency_name,
                        'المسمى الوظيفي' => $agent->job_title,
                        'المدينة' => $agent->city,
                        'العنوان' => $agent->address,
                        'الرقم الوطني' => $agent->national_id,
                        'رقم الترخيص' => $agent->license_number,
                        'سنوات الخبرة' => $agent->experience_years !== null ? $agent->experience_years.' سنة' : null,
                        'تاريخ التسجيل' => $agent->created_at?->format('Y-m-d H:i'),
                        'تاريخ التوثيق' => $agent->verified_at?->format('Y-m-d H:i'),
                    ] as $label => $value)
                        <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-700">
                            <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="font-semibold text-gray-900 dark:text-gray-100" dir="auto">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-admin.card>

            <div class="space-y-4">
                <x-admin.card title="النبذة">
                    <p class="text-sm text-gray-600 whitespace-pre-line dark:text-gray-300">{{ $agent->bio ?: '—' }}</p>
                </x-admin.card>

                <x-admin.card title="روابط ومستندات">
                    <div class="flex flex-wrap gap-2 text-sm">
                        @if ($agent->website)<a href="{{ $agent->website }}" target="_blank" rel="noopener" class="font-bold text-wajhatak-600">🌐 الموقع</a>@endif
                        @if ($agent->facebook)<a href="{{ $agent->facebook }}" target="_blank" rel="noopener" class="font-bold text-blue-600">فيسبوك</a>@endif
                        @if ($agent->instagram)<a href="{{ $agent->instagram }}" target="_blank" rel="noopener" class="font-bold text-pink-600">إنستغرام</a>@endif
                        @if ($agent->twitter)<a href="{{ $agent->twitter }}" target="_blank" rel="noopener" class="font-bold text-sky-600">X</a>@endif
                        @unless ($agent->website || $agent->facebook || $agent->instagram || $agent->twitter)
                            <span class="text-gray-400">لا توجد روابط.</span>
                        @endunless
                    </div>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                        @if ($agent->id_document_path)
                            <a href="{{ asset('storage/'.$agent->id_document_path) }}" target="_blank" rel="noopener" class="rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 font-bold text-blue-600 hover:bg-gray-50 dark:hover:bg-gray-700">🪪 صورة الهوية — عرض</a>
                        @else
                            <div class="rounded-xl border border-dashed border-gray-300 px-3 py-2 text-gray-400">لم يرفع صورة الهوية</div>
                        @endif
                        @if ($agent->license_document_path)
                            <a href="{{ asset('storage/'.$agent->license_document_path) }}" target="_blank" rel="noopener" class="rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 font-bold text-blue-600 hover:bg-gray-50 dark:hover:bg-gray-700">📜 صورة الترخيص — عرض</a>
                        @else
                            <div class="rounded-xl border border-dashed border-gray-300 px-3 py-2 text-gray-400">لم يرفع صورة الترخيص</div>
                        @endif
                    </div>
                </x-admin.card>
            </div>
        </div>

        {{-- 2. عقارات الوكيل --}}
        <div x-show="tab === 'properties'" x-cloak>
            <x-admin.card :padding="false" title="عقارات الوكيل" description="كل عقارات هذا الوكيل من قاعدة البيانات.">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الكود</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">العقار</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">النوع</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الموقع</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">السعر</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($agent->properties as $property)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 text-gray-500" dir="ltr">{{ $property->reference_code ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.properties.show', $property) }}" class="font-semibold text-wajhatak-600 hover:text-wajhatak-700">{{ $property->title }}</a>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $property->type?->name_ar ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $property->location?->city ?? '—' }}{{ $property->location?->district ? ' — '.$property->location->district : '' }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ number_format((float) $property->price) }} {{ $property->currency }}</td>
                                    <td class="px-4 py-3"><x-admin.badge :status="$property->status->value" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">لا توجد عقارات لهذا الوكيل.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>

        {{-- 3. عمليات التوثيق --}}
        <div x-show="tab === 'verification'" x-cloak class="space-y-4">
            <x-admin.card title="إجراء التوثيق الحالي">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-xs text-gray-500">الحالة الحالية</div>
                        <div class="font-bold mt-1">{{ $badge['label'] }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-xs text-gray-500">وُثّق في</div>
                        <div class="font-bold mt-1">{{ $agent->verified_at?->format('Y-m-d H:i') ?? '—' }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-xs text-gray-500">وُثّق بواسطة</div>
                        <div class="font-bold mt-1">{{ $agent->verified_by ? (\App\Models\User::find($agent->verified_by)?->name ?? '#'.$agent->verified_by) : '—' }}</div>
                    </div>
                </div>

                @if ($agent->verification_status !== 'approved' || ! $agent->is_active)
                    <div class="mt-4 flex flex-wrap items-start gap-3">
                        @if ($agent->verification_status === 'pending')
                            <form method="POST" action="{{ route('admin.agents.approve', $agent) }}">
                                @csrf
                                <x-admin.button type="submit">✓ توثيق الحساب وفتح النشر</x-admin.button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.agents.reject-verification', $agent) }}" class="flex-1 min-w-[260px] space-y-2">
                            @csrf
                            <textarea name="reason" rows="2" required placeholder="سبب الرفض (يُرسل للوكيل ويُحفظ)..." class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-600"></textarea>
                            @error('reason')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            <x-admin.button type="submit" variant="danger">✗ رفض التوثيق</x-admin.button>
                        </form>
                    </div>
                @endif
            </x-admin.card>

            <x-admin.card :padding="false" title="سجل عمليات التوثيق">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">العملية</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">بواسطة</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($verificationEvents as $event)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $event->description }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $event->user?->name ?? 'النظام' }}</td>
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $event->created_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-12 text-center text-gray-400">لا توجد عمليات توثيق مسجلة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>

        {{-- 4. سجلات التقارير --}}
        <div x-show="tab === 'reports'" x-cloak>
            <x-admin.card :padding="false" title="التقارير التي وُلّدت من حساب الوكيل" description="سجلات حقيقية من جدول سجل التقارير.">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">التقرير</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الصيغة</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">عدد السجلات</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">التاريخ</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($reportLogs as $log)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">{{ $log->typeLabel() }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $log->formatLabel() }}</td>
                                    <td class="px-4 py-3 tabular-nums text-gray-600 dark:text-gray-300">{{ number_format($log->row_count) }}</td>
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3"><a href="{{ route('admin.reports.logs.download', $log) }}" class="font-bold text-wajhatak-600 hover:text-wajhatak-700">إعادة التوليد</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">لم يولّد هذا الوكيل أي تقرير بعد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>

        {{-- 5. المحادثات --}}
        <div x-show="tab === 'conversations'" x-cloak>
            <x-admin.card :padding="false" title="محادثات الوكيل مع العملاء" description="مراسلات حقيقية من قاعدة البيانات — لا يُعرض أي محتوى إلا للمشرف.">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">العميل</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">العقار</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">آخر رسالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($conversations as $conversation)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $conversation->client?->name ?? '—' }}</div>
                                        <div class="text-xs text-gray-400" dir="ltr">{{ $conversation->client?->email ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $conversation->property?->title ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $conversation->last_message_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-12 text-center text-gray-400">لا توجد محادثات لهذا الوكيل.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>

        {{-- 6. طلبات المعاينة --}}
        <div x-show="tab === 'viewings'" x-cloak>
            <x-admin.card :padding="false" title="طلبات معاينة الوكيل">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">العقار</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">العميل</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الموعد</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($viewingRequests as $viewingRequest)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $viewingRequest->property?->title ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $viewingRequest->client?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $viewingRequest->scheduled_date?->format('Y-m-d') ?? '—' }} {{ $viewingRequest->scheduled_time ?? '' }}</td>
                                    <td class="px-4 py-3"><x-admin.badge :status="$viewingRequest->status->value" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-12 text-center text-gray-400">لا توجد طلبات معاينة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>

        {{-- 7. سجل الأنشطة --}}
        <div x-show="tab === 'activity'" x-cloak>
            <x-admin.card :padding="false" title="سجل أنشطة حساب الوكيل">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">النشاط</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">النوع</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">عنوان IP</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($activities as $activity)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $activity->description }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $activity->log_name }}</td>
                                    <td class="px-4 py-3 text-gray-500" dir="ltr">{{ $activity->ip_address ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $activity->created_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-12 text-center text-gray-400">لا توجد أنشطة مسجلة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>
    </div>
</x-admin.layouts.admin>
