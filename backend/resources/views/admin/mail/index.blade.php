<x-admin.layouts.admin heading="إعدادات البريد الإلكتروني" title="إعدادات البريد">
    <div class="max-w-5xl mx-auto space-y-6" x-data="{ tab: 'provider' }">

        {{-- تبويبات --}}
        <div class="flex gap-2 flex-wrap">
            <button @click="tab = 'provider'" :class="tab === 'provider' ? 'btn-brand' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700'" class="px-4 py-2 rounded-xl text-sm font-bold transition">المزود والإعدادات</button>
            <button @click="tab = 'logo'" :class="tab === 'logo' ? 'btn-brand' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700'" class="px-4 py-2 rounded-xl text-sm font-bold transition">شعار البريد</button>
            <button @click="tab = 'templates'" :class="tab === 'templates' ? 'btn-brand' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700'" class="px-4 py-2 rounded-xl text-sm font-bold transition">قوالب الرسائل</button>
        </div>

        {{-- ==================== تبويب المزود والإعدادات ==================== --}}
        <div x-show="tab === 'provider'" x-cloak class="space-y-6">
            <div class="rounded-xl border p-3 text-sm {{ $settings->canSend() ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300' : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300' }}">
                @if($settings->canSend())
                    ✅ الإعدادات مكتملة ومفعلة — رموز التحقق والإشعارات تُرسل.
                    @if($settings->last_tested_at)
                        <span class="mr-2">آخر اختبار: {{ $settings->last_tested_at->diffForHumans() }} ({{ $settings->last_test_result === 'success' ? 'ناجح' : 'فاشل' }})</span>
                    @endif
                @else
                    ⚠ أكمل بيانات المزود والمُرسل وفعل الإرسال حتى تعمل رسائل التحقق والإشعارات فعلًا.
                @endif
            </div>

            <form method="POST" action="{{ route('admin.mail.update') }}">
                @csrf
                <x-admin.card title="اختيار المزود" description="اختر مزود البريد الإلكتروني: SMTP التقليدي أو Resend API الحديث.">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-2">مزود البريد</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="relative flex cursor-pointer">
                                    <input type="radio" name="provider" value="smtp" @checked($settings->provider === 'smtp') class="peer sr-only">
                                    <div class="w-full rounded-xl border-2 border-gray-200 p-4 peer-checked:border-wajhatak-600 peer-checked:bg-wajhatak-50 dark:border-gray-700 dark:peer-checked:border-wajhatak-500 dark:peer-checked:bg-wajhatak-900/20 transition">
                                        <div class="font-bold text-gray-900 dark:text-gray-100">SMTP</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">خادم بريد تقليدي (Gmail, Brevo, SendGrid)</div>
                                    </div>
                                </label>
                                <label class="relative flex cursor-pointer">
                                    <input type="radio" name="provider" value="resend" @checked($settings->provider === 'resend') class="peer sr-only">
                                    <div class="w-full rounded-xl border-2 border-gray-200 p-4 peer-checked:border-wajhatak-600 peer-checked:bg-wajhatak-50 dark:border-gray-700 dark:peer-checked:border-wajhatak-500 dark:peer-checked:bg-wajhatak-900/20 transition">
                                        <div class="font-bold text-gray-900 dark:text-gray-100">Resend API</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">خدمة بريد حديثة عبر HTTPS API</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- SMTP Settings --}}
                        <div x-show="document.querySelector('input[name=\"provider\"]:checked')?.value === 'smtp'" x-cloak class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                            <h4 class="text-sm font-black text-gray-700 dark:text-gray-200 mb-4">إعدادات SMTP</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <x-admin.input label="خادم SMTP (Host)" name="smtp_host" :value="$settings->smtp_host" placeholder="smtp.example.com" />
                                </div>
                                <x-admin.input label="المنفذ" name="smtp_port" :value="$settings->smtp_port" type="number" placeholder="587" />
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">التشفير</label>
                                    <select name="smtp_encryption" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800 dark:border-gray-600">
                                        <option value="tls" @selected($settings->smtp_encryption === 'tls')">TLS (المنفذ 587)</option>
                                        <option value="ssl" @selected($settings->smtp_encryption === 'ssl')">SSL (المنفذ 465)</option>
                                        <option value="none" @selected($settings->smtp_encryption === 'none')">بدون تشفير</option>
                                    </select>
                                </div>
                                <x-admin.input label="اسم المستخدم" name="smtp_username" :value="$settings->smtp_username" placeholder="no-reply@example.com" autocomplete="off" />
                                <x-admin.input label="كلمة المرور" name="smtp_password" :value="''" type="password" placeholder="{{ $settings->smtp_password ? '•••••••• (محفوظة)' : 'كلمة مرور البريد' }}" autocomplete="new-password" />
                                <x-admin.input label="المهلة (ثوانٍ)" name="smtp_timeout" :value="$settings->smtp_timeout" type="number" min="3" max="60" />
                            </div>
                        </div>

                        {{-- Resend Settings --}}
                        <div x-show="document.querySelector('input[name=\"provider\"]:checked')?.value === 'resend'" x-cloak class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                            <h4 class="text-sm font-black text-gray-700 dark:text-gray-200 mb-4">إعدادات Resend API</h4>
                            <div class="space-y-4">
                                <x-admin.input label="مفتاح Resend API" name="resend_api_key" :value="''" type="password" placeholder="{{ $settings->resend_api_key ? '•••••••••••••••• (محفوظ)' : 're_xxxxxxxxxxxx' }}" autocomplete="off" />
                                <p class="text-xs text-gray-500 dark:text-gray-400">احصل على المفتاح من <a href="https://resend.com/api-keys" target="_blank" class="text-wajhatak-600 hover:underline">لوحة تحكم Resend</a>. يُشفّن تلقائيًا عند الحفظ.</p>
                                
                                <label class="flex items-center gap-2 text-sm font-bold cursor-pointer">
                                    <input type="checkbox" name="resend_sandbox" value="1" @checked($settings->resend_sandbox) class="h-4 w-4 accent-wajhatak-600">
                                    وضع الاختبار (Sandbox) — يسمح Resend بالإرسال للاختبار فقط إلى بريد حساب Resend المرتبط
                                </label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mr-6">فعّل هذا للتجربة قبل توثيق النطاق الخاص بك.</p>
                            </div>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card title="المُرسل العام" description="هذه البيانات تُستخدم لكل رسائل المنصة بغض النظر عن المزود.">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <x-admin.input label="اسم المُرسل" name="from_name" :value="$settings->from_name" placeholder="وجهتك" />
                        </div>
                        <div class="md:col-span-2">
                            <x-admin.input label="بريد المُرسل (From)" name="from_address" :value="$settings->from_address" placeholder="no-reply@yourdomain.com" />
                        </div>
                        <x-admin.input label="بريد الرد (Reply-To) — اختياري" name="reply_to" :value="$settings->reply_to" placeholder="support@yourdomain.com" />
                    </div>
                    @if($settings->isResend() && !$settings->resend_sandbox)
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                            ⚠️ في وضع الإنتاج، يجب توثيق النطاق في Resend قبل استخدام بريد المرسل المخصص.
                        </div>
                    @endif
                </x-admin.card>

                <x-admin.card title="تفعيل الإرسال" description="فعّل إرسال الرسائل الفعلي. عند التعطيل، لن تُرسل أي رسائل (رموز التحقق، إشعارات، إلخ).">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" @checked($settings->is_active) class="h-5 w-5 accent-wajhatak-600">
                        <span class="font-bold text-gray-900 dark:text-gray-100">تفعيل إرسال البريد الإلكتروني</span>
                    </label>
                </x-admin.card>

                <div class="flex items-center gap-3">
                    <x-admin.button type="submit">حفظ الإعدادات</x-admin.button>
                </div>
            </form>

            {{-- اختبار الاتصال والإرسال --}}
            <x-admin.card title="اختبار الاتصال والإرسال" description="تأكد من صحة الإعدادات قبل تفعيلها للإنتاج.">
                @if($settings->isResend())
                    <form method="POST" action="{{ route('admin.mail.test-resend') }}" class="mb-4">
                        @csrf
                        <div class="flex flex-col sm:flex-row gap-3 items-end">
                            <div class="flex-1 w-full">
                                <x-admin.input label="بريد لاختبار الاتصال" name="test_email" :value="auth()->user()->email" placeholder="you@example.com" />
                            </div>
                            <x-admin.button type="submit" variant="secondary">🔌 اختبار اتصال Resend API</x-admin.button>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">سيُرسل رسالة اختبار إلى هذا البريد للتحقق من صحة المفتاح والإعدادات.</p>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.mail.test-smtp') }}" class="mb-4">
                        @csrf
                        <div class="flex flex-col sm:flex-row gap-3 items-end">
                            <div class="flex-1 w-full">
                                <x-admin.input label="بريد تجريبي (اختياري)" name="test_recipient" :value="auth()->user()->email" placeholder="you@example.com" />
                            </div>
                            <x-admin.button type="submit" variant="secondary">🔌 اختبار اتصال SMTP</x-admin.button>
                        </div>
                    </form>
                @endif

                <form method="POST" action="{{ route('admin.mail.send-test') }}" class="pt-4 border-t border-gray-100 dark:border-gray-700">
                    @csrf
                    <div class="flex flex-col sm:flex-row gap-3 items-end">
                        <div class="flex-1 w-full">
                            <x-admin.input label="إرسال رسالة تجريبية إلى" name="test_email" :value="auth()->user()->email" placeholder="you@example.com" />
                        </div>
                        <x-admin.button type="submit">✉️ إرسال رسالة تجريبية</x-admin.button>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">يُمنع إرسال الرسائل إلى عناوين بريد وهمية (example.com, test.com, إلخ).</p>
                </form>
            </x-admin.card>
        </div>

        {{-- ==================== تبويب شعار البريد ==================== --}}
        <div x-show="tab === 'logo'" x-cloak class="space-y-6">
            <x-admin.card title="شعار البريد" description="شعار يظهر في رأس رسائل البريد الإلكتروني. يُرفع كصورة ويُستخدم كرابط عام في الرسائل.">
                @if($settings->logo_url)
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-2">الشعار الحالي</label>
                        <div class="inline-block p-4 border border-gray-200 dark:border-gray-700 rounded-xl">
                            <img src="{{ $settings->logo_url }}" alt="شعار البريد" style="max-width: 200px; max-height: 100px;">
                        </div>
                        <form method="POST" action="{{ route('admin.mail.logo.delete') }}" class="mt-3 inline">
                            @method('DELETE')
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:text-red-700 font-bold">حذف الشعار</button>
                        </form>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.mail.logo') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-2">رفع شعار جديد</label>
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/jpg,image/svg+xml" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800 dark:border-gray-600">
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">الصيغ المدعومة: PNG, JPG, JPEG, SVG. الحد الأقصى: 512KB.</p>
                        <x-admin.button type="submit">رفع الشعار</x-admin.button>
                    </div>
                </form>
            </x-admin.card>
        </div>

        {{-- ==================== قوالب البريد ==================== --}}
        <div x-show="tab === 'templates'" x-cloak class="space-y-5">
            {{-- Compatibility fields for the legacy settings fallback. Values come from
                 the real email_templates table when the matching template exists. --}}
            <div class="hidden" aria-hidden="true">
                @php($dbEmailTemplates = collect($emailTemplates)->keyBy('key'))
                @foreach($templates as $legacyKey => $legacyMeta)
                    @php($dbTemplate = $dbEmailTemplates->get($legacyKey))
                    <input type="text" name="templates[{{ $legacyKey }}][subject]" value="{{ old('templates.'.$legacyKey.'.subject', $dbTemplate?->subject ?? $templateValues[$legacyKey]['subject'] ?? '') }}">
                    <textarea name="templates[{{ $legacyKey }}][body]">{{ old('templates.'.$legacyKey.'.body', $dbTemplate?->text_content ?? $templateValues[$legacyKey]['body'] ?? '') }}</textarea>
                @endforeach
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex-1">
                    <h3 class="text-lg font-black">قوالب البريد الجاهزة</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">القوالب التي تستخدمها المنصة فعليًا لرموز التحقق والإشعارات وغيرها.</p>
                </div>
                <a href="{{ route('admin.email-templates.index', ['create' => 1]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-wajhatak-600 px-4 py-2.5 text-sm font-black text-white">
                    <x-admin.icon name="plus" class="h-4 w-4" /> إنشاء قالب
                </a>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse($emailTemplates as $template)
                    @php
                        $type = $template->template_type ?: 'custom';
                        $typeLabel = $emailTemplateTypes[$type] ?? $type;
                        $status = $template->status ?: ($template->is_active ? 'published' : 'archived');
                    @endphp
                    <article class="group relative overflow-hidden rounded-3xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-gray-700 dark:bg-gray-900">
                        <div class="absolute inset-x-0 top-0 h-1 bg-wajhatak-600"></div>
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800"><x-admin.icon name="mail" class="h-5 w-5 text-gray-500" /></div>
                            <div class="min-w-0 flex-1">
                                <h4 class="truncate font-black">{{ $template->name }}</h4>
                                <div class="mt-1 truncate font-mono text-[10px] text-gray-500">{{ $template->key }}</div>
                            </div>
                            <div class="flex translate-y-1 gap-1 opacity-0 transition group-hover:translate-y-0 group-hover:opacity-100">
                                <a href="{{ route('admin.email-templates.index', ['edit' => $template->id]) }}" title="تعديل" class="flex h-8 w-8 items-center justify-center rounded-lg border bg-white text-gray-700 shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200"><x-admin.icon name="edit" class="h-4 w-4" /></a>
                                <a href="{{ route('admin.email-templates.history', $template) }}" title="السجل" class="flex h-8 w-8 items-center justify-center rounded-lg border bg-white text-gray-700 shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200"><x-admin.icon name="activity" class="h-4 w-4" /></a>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="rounded-full bg-wajhatak-50 px-2.5 py-1 text-[10px] font-black text-wajhatak-700 dark:bg-wajhatak-900/30 dark:text-wajhatak-300">{{ $typeLabel }}</span>
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-black dark:bg-gray-800">{{ $status === 'published' ? 'منشور' : ($status === 'draft' ? 'مسودة' : 'مؤرشف') }}</span>
                            @if($template->is_system)<span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-black text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">نظامي</span>@endif
                        </div>
                        <p class="mt-3 line-clamp-2 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $template->description ?: 'قالب بريد قابل للتحرير.' }}</p>
                        <a href="{{ route('admin.email-templates.index', ['edit' => $template->id]) }}" class="mt-4 block rounded-xl border px-3 py-2 text-center text-xs font-black hover:border-wajhatak-500 hover:text-wajhatak-700 dark:border-gray-700">تعديل القالب</a>
                    </article>
                @empty
                    <div class="col-span-full rounded-3xl border border-dashed p-10 text-center text-sm text-gray-500">لا توجد قوالب بريد.</div>
                @endforelse
            </div>

            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-xs leading-6 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <strong>ملاحظة تشغيلية:</strong> المفتاح البرمجي يحدد القالب الذي تستدعيه خدمات المنصة، بينما نوع القالب ينظم القوالب داخل لوحة الإدارة ولا يغيّر مسار الإرسال تلقائيًا.
            </div>
        </div>
    </div>
</x-admin.layouts.admin>
