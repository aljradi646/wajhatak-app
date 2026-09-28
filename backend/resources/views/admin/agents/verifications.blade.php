<x-admin.layouts.admin heading="طلبات توثيق الوكلاء" title="طلبات التوثيق" :breadcrumbs="[['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'الوكلاء', 'url' => route('admin.agents.index')]]">
    <div class="space-y-5">

        @forelse($agents as $agent)
            <x-admin.card>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    {{-- بيانات الحساب --}}
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            @if($agent->photo_path)
                                <img src="{{ asset('storage/'.$agent->photo_path) }}" class="h-14 w-14 rounded-2xl object-cover" alt="صورة الوكيل">
                            @else
                                <span class="flex h-14 w-14 items-center justify-center rounded-2xl text-xl font-black text-white brand-mark">{{ mb_substr($agent->user->name, 0, 1) }}</span>
                            @endif
                            <div>
                                <div class="font-black text-gray-900 dark:text-gray-100">{{ $agent->user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $agent->user->email }}</div>
                                <div class="text-xs text-gray-400">سُجّل {{ $agent->created_at->format('Y/m/d') }}</div>
                            </div>
                        </div>
                        <dl class="text-sm space-y-1.5 text-gray-600 dark:text-gray-300">
                            <div><span class="font-bold">المكتب:</span> {{ $agent->agency_name ?? '—' }}</div>
                            <div><span class="font-bold">المسمى:</span> {{ $agent->job_title ?? '—' }}</div>
                            <div><span class="font-bold">الجوال:</span> {{ $agent->phone ?? $agent->user->phone ?? '—' }}</div>
                            <div><span class="font-bold">واتساب:</span> {{ $agent->whatsapp ?? '—' }}</div>
                            <div><span class="font-bold">المدينة:</span> {{ $agent->city ?? '—' }}</div>
                            <div><span class="font-bold">الرقم الوطني:</span> {{ $agent->national_id ?? '—' }}</div>
                            <div><span class="font-bold">رقم الترخيص:</span> {{ $agent->license_number ?? '—' }}</div>
                            <div><span class="font-bold">الخبرة:</span> {{ $agent->experience_years ? $agent->experience_years.' سنة' : '—' }}</div>
                        </dl>
                    </div>

                    {{-- المستندات والروابط --}}
                    <div>
                        <h4 class="text-sm font-black mb-3 text-gray-700 dark:text-gray-200">المستندات والروابط</h4>
                        <div class="space-y-2 text-sm">
                            @if($agent->id_document_path)
                                <a href="{{ asset('storage/'.$agent->id_document_path) }}" target="_blank" class="block rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 text-blue-600 font-bold">🪪 صورة الهوية — عرض</a>
                            @else
                                <div class="rounded-xl border border-dashed border-gray-300 px-3 py-2 text-gray-400">لم يرفع صورة الهوية</div>
                            @endif
                            @if($agent->license_document_path)
                                <a href="{{ asset('storage/'.$agent->license_document_path) }}" target="_blank" class="block rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 text-blue-600 font-bold">📜 صورة الترخيص — عرض</a>
                            @else
                                <div class="rounded-xl border border-dashed border-gray-300 px-3 py-2 text-gray-400">لم يرفع صورة الترخيص</div>
                            @endif
                            @if($agent->bio)
                                <p class="text-gray-600 dark:text-gray-300 leading-relaxed pt-1">{{ \Illuminate\Support\Str::limit($agent->bio, 220) }}</p>
                            @endif
                            <div class="flex flex-wrap gap-2 pt-1">
                                @if($agent->website)<a href="{{ $agent->website }}" target="_blank" class="text-xs font-bold text-wajhatak-600">🌐 موقع</a>@endif
                                @if($agent->facebook)<a href="{{ $agent->facebook }}" target="_blank" class="text-xs font-bold text-blue-600">فيسبوك</a>@endif
                                @if($agent->instagram)<a href="{{ $agent->instagram }}" target="_blank" class="text-xs font-bold text-pink-600">إنستغرام</a>@endif
                                @if($agent->twitter)<a href="{{ $agent->twitter }}" target="_blank" class="text-xs font-bold text-sky-600">X</a>@endif
                            </div>
                        </div>
                    </div>

                    {{-- الإجراءات --}}
                    <div class="flex flex-col gap-3 justify-center">
                        <form method="POST" action="{{ route('admin.agents.approve', $agent) }}" onsubmit="return confirm('توثيق الوكيل سيفتح له بوابة نشر العقارات. متابعة؟');">
                            @csrf
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold text-white rounded-xl" style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);">
                                ✓ توثيق الحساب وفتح النشر
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.agents.reject-verification', $agent) }}" class="space-y-2">
                            @csrf
                            <textarea name="reason" rows="2" required placeholder="سبب الرفض (يُرسل للوكيل)..." class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-600"></textarea>
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold text-red-600 rounded-xl border border-red-200 hover:bg-red-50 dark:hover:bg-red-900/20">
                                ✗ رفض الطلب
                            </button>
                        </form>
                        <a href="{{ route('admin.agents.edit', $agent) }}" class="text-center text-sm text-gray-500 hover:text-gray-700">تعديل بياناته يدويًا</a>
                    </div>
                </div>
            </x-admin.card>
        @empty
            <x-admin.card>
                <div class="py-10 text-center text-gray-500">
                    لا توجد طلبات توثيق معلقة حاليًا — كل الوكلاء تمت مراجعتهم ✓
                </div>
            </x-admin.card>
        @endforelse

        @include('admin.partials.pagination', ['paginator' => $agents])
    </div>
</x-admin.layouts.admin>
