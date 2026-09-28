<x-admin.layouts.admin heading="إعدادات البريد الإلكتروني" title="إعدادات البريد">
    <div class="max-w-5xl mx-auto space-y-6" x-data="{ tab: 'smtp' }">

        {{-- تبويبات --}}
        <div class="flex gap-2">
            <button @click="tab = 'smtp'" :class="tab === 'smtp' ? 'btn-brand' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700'" class="px-4 py-2 rounded-xl text-sm font-bold transition">خادم SMTP</button>
            <button @click="tab = 'templates'" :class="tab === 'templates' ? 'btn-brand' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700'" class="px-4 py-2 rounded-xl text-sm font-bold transition">قوالب الرسائل</button>
        </div>

        {{-- ==================== تبويب SMTP ==================== --}}
        <div x-show="tab === 'smtp'" x-cloak class="space-y-6">
            <div class="rounded-xl border p-3 text-sm {{ $configured ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300' : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300' }}">
                @if($configured)
                    ✅ الإعدادات مكتملة — رموز التحقق والإشعارات تُرسل عبر خادمك.
                @else
                    ⚠ أكمل بيانات الخادم والمُرسل حتى تعمل رسائل التحقق والإشعارات فعلًا.
                @endif
            </div>

            <form method="POST" action="{{ route('admin.mail.update') }}">
                @csrf
                <x-admin.card title="بيانات خادم SMTP" description="أدخل بيانات مزود بريدك (مثل بريد الموقع أو Gmail/Brevo/SendGrid). تُحفظ في قاعدة البيانات وتُستخدم لكل رسائل المنصة.">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <x-admin.input label="خادم SMTP (Host)" name="mail_host" :value="$values['mail_host']" placeholder="smtp.example.com" />
                        </div>
                        <x-admin.input label="المنفذ" name="mail_port" :value="$values['mail_port']" type="number" placeholder="587" />
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">التشفير</label>
                            <select name="mail_encryption" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800 dark:border-gray-600">
                                <option value="tls" @selected($values['mail_encryption'] === 'tls')>TLS (المنفذ 587)</option>
                                <option value="ssl" @selected($values['mail_encryption'] === 'ssl')>SSL (المنفذ 465)</option>
                                <option value="none" @selected($values['mail_encryption'] === 'none')>بدون تشفير</option>
                            </select>
                        </div>
                        <x-admin.input label="اسم المستخدم" name="mail_username" :value="$values['mail_username']" placeholder="no-reply@example.com" autocomplete="off" />
                        <x-admin.input label="كلمة المرور" name="mail_password" :value="''" type="password" placeholder="{{ $values['mail_password'] !== '' ? '•••••••• (محفوظة — اتركه فارغًا للإبقاء)' : 'كلمة مرور البريد' }}" autocomplete="new-password" />
                        <div class="md:col-span-2">
                            <x-admin.input label="بريد المُرسل (From)" name="mail_from_address" :value="$values['mail_from_address']" placeholder="no-reply@example.com" />
                        </div>
                        <x-admin.input label="اسم المُرسل" name="mail_from_name" :value="$values['mail_from_name']" placeholder="وجهتك" />
                        <x-admin.input label="بريد الرد (Reply-To) — اختياري" name="mail_reply_to" :value="$values['mail_reply_to']" placeholder="support@example.com" />
                        <x-admin.input label="المهلة (ثوانٍ)" name="mail_timeout" :value="$values['mail_timeout']" type="number" min="3" max="60" />
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <h4 class="text-sm font-black text-gray-700 dark:text-gray-200 mb-3">إعدادات رمز التحقق من البريد</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <x-admin.input label="صلاحية الرمز (دقائق)" name="mail_verification_code_ttl" :value="$values['mail_verification_code_ttl']" type="number" min="5" max="120" />
                            <x-admin.input label="أقصى محاولات إدخال" name="mail_verification_max_attempts" :value="$values['mail_verification_max_attempts']" type="number" min="3" max="10" />
                            <x-admin.input label="فاصل إعادة الإرسال (ثوانٍ)" name="mail_verification_resend_seconds" :value="$values['mail_verification_resend_seconds']" type="number" min="15" max="600" />
                        </div>
                        <label class="mt-3 flex items-center gap-2 text-sm font-bold">
                            <input type="checkbox" name="mail_require_mx_check" value="1" @checked($values['mail_require_mx_check']) class="h-4 w-4 accent-wajhatak-600">
                            رفض البريد الوهمي: لا إرسال إلا للنطاقات التي تستقبل بريدًا فعلًا (MX حقيقي) (موصى به)
                        </label>
                    </div>

                    <div class="mt-6">
                        <x-admin.button type="submit">حفظ الإعدادات</x-admin.button>
                    </div>
                </x-admin.card>
            </form>

            {{-- اختبار الاتصال --}}
            <x-admin.card title="اختبار الاتصال" description="يجرّب الاتصال بخادم SMTP حقيقيًا: فتح قناة + تشفير + تسجيل دخول، ويتحقق من قبول المستلم دون إرسال بريد.">
                <form method="POST" action="{{ route('admin.mail.test') }}">
                    @csrf
                    <div class="flex flex-col sm:flex-row gap-3 items-end">
                        <div class="flex-1 w-full">
                            <x-admin.input label="بريد تجريبي (اختياري — يفحص قبول الخادم له)" name="test_recipient" :value="auth()->user()->email" placeholder="you@example.com" />
                        </div>
                        <x-admin.button type="submit" variant="secondary">🔌 اختبار الاتصال الآن</x-admin.button>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.mail.send-test') }}" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    @csrf
                    <div class="flex flex-col sm:flex-row gap-3 items-end">
                        <div class="flex-1 w-full">
                            <x-admin.input label="أو أرسل رسالة تجريبية حقيقية إلى" name="test_email" :value="auth()->user()->email" placeholder="you@example.com" />
                        </div>
                        <x-admin.button type="submit">✉️ إرسال رسالة تجريبية</x-admin.button>
                    </div>
                </form>
            </x-admin.card>
        </div>

        {{-- ==================== تبويب القوالب ==================== --}}
        <div x-show="tab === 'templates'" x-cloak class="space-y-6">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 text-sm text-gray-600 dark:text-gray-300">
                المتغيرات المتاحة داخل أي قالب:
                <code class="mx-1 rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5">{name}</code>
                <code class="mx-1 rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5">{code}</code>
                <code class="mx-1 rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5">{ttl}</code>
                <code class="mx-1 rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5">{reason}</code>
                <code class="mx-1 rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5">{property}</code>
            </div>

            <form method="POST" action="{{ route('admin.mail.templates') }}">
                @csrf
                @foreach($templates as $key => $meta)
                    <x-admin.card :title="$meta['label']" class="mb-5">
                        <div class="space-y-3">
                            <x-admin.input label="عنوان الرسالة" name="templates[{{ $key }}][subject]" :value="$templateValues[$key]['subject']" />
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">نص الرسالة</label>
                                <textarea name="templates[{{ $key }}][body]" rows="6" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm leading-relaxed dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100">{{ $templateValues[$key]['body'] }}</textarea>
                            </div>
                        </div>
                    </x-admin.card>
                @endforeach
                <x-admin.button type="submit">حفظ كل القوالب</x-admin.button>
            </form>
        </div>
    </div>
</x-admin.layouts.admin>
