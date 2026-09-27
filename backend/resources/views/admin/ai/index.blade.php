<x-admin.layouts.admin heading="المساعد الذكي" title="المساعد الذكي" :breadcrumbs="[['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')]]">

    <div class="space-y-6" x-data="{ tab: 'settings' }">

        {{-- ============ التبويبات ============ --}}
        <div class="flex flex-wrap gap-2">
            <button @click="tab = 'settings'" :class="tab === 'settings' ? 'btn-brand' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700'" class="px-4 py-2 rounded-xl text-sm font-bold transition">الإعدادات</button>
            <button @click="tab = 'monitoring'" :class="tab === 'monitoring' ? 'btn-brand' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700'" class="px-4 py-2 rounded-xl text-sm font-bold transition">المراقبة</button>
            <button @click="tab = 'logs'" :class="tab === 'logs' ? 'btn-brand' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700'" class="px-4 py-2 rounded-xl text-sm font-bold transition">سجل الطلبات</button>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-500/10 dark:border-green-500/30 px-4 py-3 text-sm font-bold text-green-700 dark:text-green-400">
                {{ session('status') }}
            </div>
        @endif

        {{-- ==================== تبويب الإعدادات ==================== --}}
        <div x-show="tab === 'settings'" class="space-y-6">
            <form method="POST" action="{{ route('admin.ai.update') }}">
                @csrf

                {{-- ============ General ============ --}}
                <x-admin.card title="عام" description="تفعيل المساعد وهويته في التطبيق.">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center gap-3 rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                            <input type="checkbox" name="ai_enabled" value="1" @checked($values['ai_enabled']) class="h-5 w-5 rounded accent-wajhatak-600">
                            <span class="text-sm font-bold">تفعيل المساعد الذكي</span>
                        </label>
                        <div>
                            <x-admin.input label="اسم المساعد" name="ai_assistant_name" :value="$values['ai_assistant_name']" />
                        </div>
                        <div class="md:col-span-2">
                            <x-admin.textarea label="رسالة الترحيب" name="ai_welcome_message" :value="$values['ai_welcome_message']" rows="3" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">اللغة الافتراضية</label>
                            <select name="ai_default_language" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800 dark:border-gray-600">
                                <option value="ar" @selected($values['ai_default_language'] === 'ar')>العربية</option>
                                <option value="en" @selected($values['ai_default_language'] === 'en')>English</option>
                            </select>
                        </div>
                    </div>
                </x-admin.card>

                {{-- ============ Model ============ --}}
                <x-admin.card title="النموذج" description="الاتصال بمحرك الاستدلال المحلي (Self-Hosted). لا توجد أي مفاتيح سحابية — يبقى عنوان الخادم داخل شبكتك.">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">المزود</label>
                            <select name="ai_provider" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800 dark:border-gray-600">
                                @foreach ($providers as $provider)
                                    <option value="{{ $provider }}" @selected($values['ai_provider'] === $provider)>{{ $provider }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-admin.input label="اسم النموذج" name="ai_model" :value="$values['ai_model']" placeholder="glm-4.6" />
                        <div class="md:col-span-2">
                            <x-admin.input label="نقطة نهاية الاستدلال (داخلية)" name="ai_inference_endpoint" :value="$values['ai_inference_endpoint']" placeholder="http://ai-server:8000/v1" />
                        </div>
                        <x-admin.input label="درجة الحرارة (0 - 1)" name="ai_temperature" :value="$values['ai_temperature']" type="number" step="0.05" min="0" max="1" />
                        <x-admin.input label="أقصى عدد رموز للمخرجات" name="ai_max_tokens" :value="$values['ai_max_tokens']" type="number" min="100" max="4000" />
                        <x-admin.input label="نافذة السياق" name="ai_context_window" :value="$values['ai_context_window']" type="number" min="1024" />
                        <x-admin.input label="المهلة بالثواني" name="ai_timeout" :value="$values['ai_timeout']" type="number" min="5" max="120" placeholder="افتراضي من البيئة" />
                        <div class="md:col-span-2 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-dashed border-gray-300 dark:border-gray-600 p-3 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                            🔒 مفاتيح خادم الاستدلال لا تُعرض هنا ولا تُرسل للتطبيق أبدًا — تُدار فقط عبر متغيرات البيئة (AI_INFERENCE_API_KEY). حالة الخادم الآن:
                            <span class="font-bold {{ $health->healthy ? 'text-green-600' : 'text-red-500' }}">{{ $health->healthy ? 'يعمل ✓' : 'غير متصل ✗' }}</span>
                            @if ($health->latencyMs) ({{ $health->latencyMs }}ms) @endif
                        </div>
                    </div>
                </x-admin.card>

                {{-- ============ Behavior ============ --}}
                <x-admin.card title="السلوك" description="شخصية المساعد وأسلوب الرد وحدود البحث. قواعد السلامة الإلزامية محمية في الكود ولا يمكن تعطيلها.">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <x-admin.textarea label="توجيهات إضافية للنظام (اختياري)" name="ai_system_prompt" :value="$values['ai_system_prompt']" rows="3" placeholder="تُطبق فقط إن لم تتعارض مع قواعد السلامة الإلزامية." />
                        </div>
                        <div class="md:col-span-2">
                            <x-admin.textarea label="شخصية المساعد" name="ai_personality" :value="$values['ai_personality']" rows="2" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">أسلوب الرد</label>
                            <select name="ai_response_style" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800 dark:border-gray-600">
                                <option value="concise" @selected($values['ai_response_style'] === 'concise')>مختصر</option>
                                <option value="detailed" @selected($values['ai_response_style'] === 'detailed')>مفصّل</option>
                            </select>
                        </div>
                        <x-admin.input label="أقصى عدد نتائج بحث" name="ai_max_results" :value="$values['ai_max_results']" type="number" min="1" max="6" />
                        <x-admin.input label="أدنى درجة مطابقة (0 - 1)" name="ai_min_match_score" :value="$values['ai_min_match_score']" type="number" step="0.01" min="0" max="1" />
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:col-span-2">
                            <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="ai_allow_comparison" value="1" @checked($values['ai_allow_comparison']) class="h-4 w-4 accent-wajhatak-600"> السماح بالمقارنة</label>
                            <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="ai_allow_recommendations" value="1" @checked($values['ai_allow_recommendations']) class="h-4 w-4 accent-wajhatak-600"> السماح بالتوصيات</label>
                            <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="ai_allow_followups" value="1" @checked($values['ai_allow_followups']) class="h-4 w-4 accent-wajhatak-600"> أسئلة المتابعة</label>
                        </div>
                    </div>
                </x-admin.card>

                {{-- ============ Scope ============ --}}
                <x-admin.card title="نطاق المعرفة" description="مصادر البيانات المسموح للمساعد بالاعتماد عليها.">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="ai_scope_properties" value="1" @checked($values['ai_scope_properties']) class="h-4 w-4 accent-wajhatak-600"> العقارات</label>
                        <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="ai_scope_locations" value="1" @checked($values['ai_scope_locations']) class="h-4 w-4 accent-wajhatak-600"> المواقع</label>
                        <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="ai_scope_features" value="1" @checked($values['ai_scope_features']) class="h-4 w-4 accent-wajhatak-600"> المزايا</label>
                        <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="ai_scope_availability" value="1" @checked($values['ai_scope_availability']) class="h-4 w-4 accent-wajhatak-600"> التوفر</label>
                        <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="ai_scope_faq" value="1" @checked($values['ai_scope_faq']) class="h-4 w-4 accent-wajhatak-600"> أسئلة المنصة الشائعة</label>
                    </div>
                </x-admin.card>

                {{-- ============ Guardrails ============ --}}
                <x-admin.card title="الحواجز الأمنية" description="حماية ضد الهلوسة وحقن التعليمات وتسرب البيانات. الثلاثة الأولى مفروضة في الكود ولا يمكن تعطيلها.">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="ai_guard_domain_restriction" value="1" @checked($values['ai_guard_domain_restriction']) class="h-4 w-4 accent-wajhatak-600"> قصر النطاق على العقارات</label>
                        <label class="flex items-center gap-2 text-sm font-bold opacity-60"><input type="checkbox" checked disabled class="h-4 w-4 accent-wajhatak-600"> حماية من الهلوسة (إلزامي)</label>
                        <label class="flex items-center gap-2 text-sm font-bold opacity-60"><input type="checkbox" checked disabled class="h-4 w-4 accent-wajhatak-600"> حماية من حقن التعليمات (إلزامي)</label>
                        <label class="flex items-center gap-2 text-sm font-bold opacity-60"><input type="checkbox" checked disabled class="h-4 w-4 accent-wajhatak-600"> حماية البيانات الحساسة (إلزامي)</label>
                        <div class="sm:col-span-2">
                            <x-admin.textarea label="رد الطلبات خارج النطاق" name="ai_out_of_scope_response" :value="$values['ai_out_of_scope_response']" rows="2" />
                        </div>
                    </div>
                </x-admin.card>

                {{-- ============ Search ============ --}}
                <x-admin.card title="البحث" description="استراتيجية ترتيب النتائج وحدود المترشحين.">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="ai_semantic_ranking" value="1" @checked($values['ai_semantic_ranking']) class="h-4 w-4 accent-wajhatak-600"> تمكين الترتيب الدلالي</label>
                        <x-admin.input label="عتبة التشابه (0 - 1)" name="ai_similarity_threshold" :value="$values['ai_similarity_threshold']" type="number" step="0.01" min="0" max="1" />
                        <x-admin.input label="أقصى عدد مترشحين" name="ai_max_candidates" :value="$values['ai_max_candidates']" type="number" min="10" max="60" />
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">استراتيجية الترتيب</label>
                            <select name="ai_sort_strategy" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800 dark:border-gray-600">
                                <option value="relevance" @selected($values['ai_sort_strategy'] === 'relevance')>الأكثر ملاءمة</option>
                                <option value="price_asc" @selected($values['ai_sort_strategy'] === 'price_asc')>الأرخص أولًا</option>
                                <option value="price_desc" @selected($values['ai_sort_strategy'] === 'price_desc')>الأغلى أولًا</option>
                            </select>
                        </div>
                        <x-admin.input label="نصف قطر البحث الافتراضي (كم)" name="ai_default_search_radius_km" :value="$values['ai_default_search_radius_km']" type="number" min="1" max="100" />
                    </div>
                </x-admin.card>

                {{-- ============ Conversation ============ --}}
                <x-admin.card title="المحادثة" description="سياق الحوار ومدة الإبقاء وسياسة المسح.">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="ai_history_enabled" value="1" @checked($values['ai_history_enabled']) class="h-4 w-4 accent-wajhatak-600"> تمكين سجل المحادثة</label>
                        <x-admin.input label="مدة الإبقاء (أيام)" name="ai_history_retention_days" :value="$values['ai_history_retention_days']" type="number" min="1" max="365" />
                        <x-admin.input label="أقصى رسائل محفوظة لكل محادثة" name="ai_max_messages" :value="$values['ai_max_messages']" type="number" min="10" max="200" />
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">سياسة المسح</label>
                            <select name="ai_clear_policy" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800 dark:border-gray-600">
                                <option value="soft" @selected($values['ai_clear_policy'] === 'soft')>أرشفة (soft)</option>
                                <option value="hard" @selected($values['ai_clear_policy'] === 'hard')>حذف نهائي (hard)</option>
                            </select>
                        </div>
                    </div>
                </x-admin.card>

                <div class="flex justify-end">
                    <x-admin.button type="submit">حفظ كل الإعدادات</x-admin.button>
                </div>
            </form>
        </div>

        {{-- ==================== تبويب المراقبة ==================== --}}
        <div x-show="tab === 'monitoring'" x-cloak class="space-y-6">
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

            <x-admin.card title="صحة النموذج المحلي" description="فحص مباشر لخادم الاستدلال عبر نقطة /models.">
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 font-bold {{ $health->healthy ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400' }}">
                        {{ $health->healthy ? '● يعمل' : '● غير متصل' }}
                    </span>
                    <span>المزود: <b>{{ $health->provider }}</b></span>
                    @if ($health->latencyMs)<span>زمن الفحص: <b>{{ $health->latencyMs }}ms</b></span>@endif
                    @if ($health->message)<span class="text-gray-500">{{ $health->message }}</span>@endif
                </div>
                @if (!empty($health->details['available_models']))
                    <p class="mt-3 text-xs text-gray-500">النماذج المتاحة: {{ implode('، ', array_slice($health->details['available_models'], 0, 6)) }}</p>
                @endif
            </x-admin.card>

            <x-admin.card title="مزامنة فهرس البحث" description="الفهرس يتحدث تلقائيًا عند كل تغيير في العقارات. هذا الزر لإعادة البناء اليدوي.">
                <form method="POST" action="{{ route('admin.ai.reindex') }}">
                    @csrf
                    <x-admin.button type="submit" variant="secondary">إعادة بناء الفهرس الآن</x-admin.button>
                </form>
            </x-admin.card>
        </div>

        {{-- ==================== تبويب السجل ==================== --}}
        <div x-show="tab === 'logs'" x-cloak class="space-y-4">
            <x-admin.card :padding="false" title="سجل طلبات المساعد" description="بيانات تشغيلية آمنة — لا تُسجل أسرار ولا محتوى رسائل المستخدمين الحرة.">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الوقت</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">النية</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">المعايير</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">النتائج</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الحالة</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الزمن</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">البحث</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($logs as $log)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $log->created_at?->format('m/d H:i') }}</td>
                                    <td class="px-4 py-3 font-bold">{{ $log->intent ?? '—' }}</td>
                                    <td class="px-4 py-3 max-w-xs truncate text-xs text-gray-500" dir="ltr">{{ $log->structured_filters ? json_encode($log->structured_filters, JSON_UNESCAPED_UNICODE) : '—' }}</td>
                                    <td class="px-4 py-3">{{ $log->results_count }}</td>
                                    <td class="px-4 py-3">
                                        @php($colors = ['ok' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400', 'blocked' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400', 'error' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400', 'fallback' => 'bg-gray-100 text-gray-600'])
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $colors[$log->status] ?? 'bg-gray-100' }}">{{ $log->status }}</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $log->latency_ms }}ms</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $log->search_ms }}ms</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">لا توجد طلبات مسجلة بعد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3">{{ $logs->links() }}</div>
            </x-admin.card>
        </div>

    </div>
</x-admin.layouts.admin>
