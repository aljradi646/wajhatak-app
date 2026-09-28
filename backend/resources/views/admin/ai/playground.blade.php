<x-admin.layouts.admin heading="اختبار المساعد الذكي" title="اختبار المساعد" :breadcrumbs="[['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'المساعد الذكي', 'url' => route('admin.ai.index')]]">

    <div class="max-w-4xl mx-auto space-y-4" x-data="aiPlayground()">

        {{-- شريط الحالة --}}
        <div class="rounded-2xl border p-4 flex flex-wrap items-center gap-3 text-sm {{ $enabled ? 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800' : 'border-amber-300 bg-amber-50 dark:bg-amber-500/10' }}">
            <span class="font-black text-base">{{ $assistantName }}</span>
            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 font-bold text-xs bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400">
                ● المحرك الحتمي جاهز
            </span>
            @unless($enabled)
                <span class="text-amber-700 dark:text-amber-400 font-bold">⚠ المساعد معطل — فعّله من تبويب الإعدادات</span>
            @endunless
            <form method="POST" action="{{ route('admin.ai.playground.clear') }}" class="ms-auto">
                @csrf
                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-bold">🗑 مسح المحادثة</button>
            </form>
        </div>

        {{-- نافذة المحادثة --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden flex flex-col" style="height: 560px;">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <span class="h-9 w-9 rounded-xl bg-gradient-to-br from-wajhatak-600 to-wajhatak-400 flex items-center justify-center text-white">
                    <x-admin.icon name="ai-assistant" class="h-5 w-5" />
                </span>
                <div>
                    <div class="font-black text-gray-900 dark:text-gray-100">محادثة اختبار حقيقية</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">تُمرر عبر نفس محرك التطبيق: حواجز ← نية ← بحث حقيقي في القاعدة ← محرك ردود حتمي</div>
                </div>
            </div>

            {{-- الرسائل --}}
            <div id="ai-messages" class="flex-1 overflow-y-auto px-5 py-4 space-y-3 bg-gray-50 dark:bg-gray-900/40">
                @forelse($messages as $message)
                    @include('admin.ai.partials.playground-message', ['message' => $message])
                @empty
                    <div class="text-center text-gray-400 py-12 text-sm" id="ai-empty">
                        اكتب رسالة في الأسفل — جرّب: «أريد شقة غرفتين في صنعاء» أو «أرخص العقارات» أو «اكتب لي كود PHP» (لترى الحاجز)
                    </div>
                @endforelse
            </div>

            {{-- الإدخال --}}
            <form id="ai-form" class="p-4 border-t border-gray-100 dark:border-gray-700 flex gap-2 bg-white dark:bg-gray-800">
                <input id="ai-input" type="text" dir="rtl" autocomplete="off" placeholder="اكتب طلبك العقاري…"
                       class="flex-1 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-wajhatak-400 focus:ring-2 focus:ring-wajhatak-300/50 dark:bg-gray-900 dark:border-gray-600 dark:text-gray-100"
                       {{ $enabled ? '' : 'disabled' }}>
                <button id="ai-send" type="submit" {{ $enabled ? '' : 'disabled' }}
                        class="btn-brand px-5 py-2.5 rounded-xl text-sm font-bold disabled:opacity-50">
                    إرسال
                </button>
            </form>
        </div>

        {{-- أمثلة سريعة --}}
        <div class="flex flex-wrap gap-2" id="ai-examples">
            @foreach(['أريد شقة غرفتين في صنعاء', 'أرخص العقارات', 'شقق مفروشة أقل من 150 ألف', 'أريد بيت في حدة', 'اكتب لي برنامج Flutter'] as $example)
                <button type="button" class="px-3 py-1.5 rounded-full text-xs font-bold border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:border-wajhatak-400 hover:text-wajhatak-600 transition"
                        x-on:click="fillExample('{{ $example }}')">{{ $example }}</button>
            @endforeach
        </div>
    </div>

    @push('scripts')
    <script>
        function aiPlayground() {
            return {
                sending: false,
                init() {
                    this.scrollBottom();
                    document.getElementById('ai-form').addEventListener('submit', (e) => {
                        e.preventDefault();
                        this.send();
                    });
                },
                fillExample(text) {
                    const input = document.getElementById('ai-input');
                    input.value = text;
                    input.focus();
                },
                scrollBottom() {
                    const box = document.getElementById('ai-messages');
                    if (box) box.scrollTop = box.scrollHeight;
                },
                async send() {
                    const input = document.getElementById('ai-input');
                    const text = input.value.trim();
                    if (!text || this.sending) return;
                    this.sending = true;
                    input.value = '';
                    input.disabled = true;
                    document.getElementById('ai-send').disabled = true;

                    // 1) فقاعة المستخدم فورًا.
                    this.appendBubble('user', text);

                    // 2) مؤشر انتظار.
                    const typing = this.appendTyping();

                    try {
                        const response = await fetch('{{ route('admin.ai.playground.send') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ message: text }),
                        });
                        const json = await response.json();
                        const payload = json.data ?? json;
                        typing.remove();
                        this.appendBubble('assistant', payload.reply ?? '—', payload.properties ?? []);
                        if (payload.filters && Object.keys(payload.filters).length > 0) {
                            this.appendFilters(payload.filters);
                        }
                    } catch (e) {
                        typing.remove();
                        this.appendBubble('assistant', 'تعذر الاتصال بالخادم. أعد المحاولة.');
                    } finally {
                        this.sending = false;
                        input.disabled = false;
                        document.getElementById('ai-send').disabled = false;
                        input.focus();
                        this.scrollBottom();
                    }
                },
                escape(text) {
                    const div = document.createElement('div');
                    div.textContent = text ?? '';
                    return div.innerHTML;
                },
                appendBubble(role, content, properties) {
                    const box = document.getElementById('ai-messages');
                    document.getElementById('ai-empty')?.remove();
                    const isUser = role === 'user';
                    const wrap = document.createElement('div');
                    wrap.className = isUser ? 'flex justify-start' : 'flex justify-end';

                    let cards = '';
                    for (const p of (properties || [])) {
                        const price = (p.price ?? 0).toLocaleString('en-US');
                        cards += `
                            <div class="mt-2 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-3">
                                <div class="font-black text-sm text-gray-900 dark:text-gray-100">${this.escape(p.title)}</div>
                                <div class="text-xs mt-1 text-wajhatak-600 dark:text-wajhatak-400 font-bold">${price} ${this.escape(p.currency ?? '')}</div>
                                <div class="text-xs text-gray-500 mt-1">${this.escape([p.district, p.city].filter(Boolean).join(' - ') || 'الموقع غير محدد')} ${p.bedrooms ? '• ' + p.bedrooms + ' غرف' : ''}</div>
                                <div class="text-[10px] mt-1.5 text-gray-400">property_id: ${p.property_id} ${p.available ? '• متاح' : '• غير متاح'}</div>
                            </div>`;
                    }

                    wrap.innerHTML = `
                        <div class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm leading-relaxed ${isUser
                            ? 'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-ss-md'
                            : 'bg-wajhatak-50 dark:bg-wajhatak-500/10 border border-wajhatak-100 dark:border-wajhatak-500/20 text-gray-800 dark:text-gray-100 rounded-se-md'}">
                            <div class="whitespace-pre-wrap">${this.escape(content)}</div>
                            ${cards}
                        </div>`;
                    box.appendChild(wrap);
                    this.scrollBottom();
                },
                appendTyping() {
                    const box = document.getElementById('ai-messages');
                    const el = document.createElement('div');
                    el.className = 'flex justify-end';
                    el.innerHTML = `<div class="rounded-2xl px-4 py-3 bg-wajhatak-50 dark:bg-wajhatak-500/10 border border-wajhatak-100 dark:border-wajhatak-500/20 flex gap-1.5 items-center">
                        ${[0, 1, 2].map(i => `<span class="h-2 w-2 rounded-full bg-wajhatak-400 animate-pulse" style="animation-delay:${i * 150}ms"></span>`).join('')}
                    </div>`;
                    box.appendChild(el);
                    this.scrollBottom();
                    return el;
                },
                appendFilters(filters) {
                    const box = document.getElementById('ai-messages');
                    const el = document.createElement('div');
                    el.className = 'flex justify-end';
                    el.innerHTML = `<details class="max-w-[85%] text-xs">
                        <summary class="cursor-pointer text-gray-400 hover:text-gray-500 font-bold">المعايير المستخلصة 🔍</summary>
                        <pre class="mt-1 rounded-lg bg-gray-900 text-gray-200 p-3 overflow-x-auto text-[11px]" dir="ltr">${this.escape(JSON.stringify(filters, null, 2))}</pre>
                    </details>`;
                    box.appendChild(el);
                    this.scrollBottom();
                },
            };
        }
    </script>
    @endpush
</x-admin.layouts.admin>
