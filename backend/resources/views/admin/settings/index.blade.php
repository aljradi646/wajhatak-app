<x-admin.layouts.admin heading="الإعدادات" title="الإعدادات" :breadcrumbs="[['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')]]">

    <div class="space-y-6">
        {{-- ====================== Platform identity ====================== --}}
        <x-admin.card title="هوية المنصة" description="اسم المنصة وشعارها ومعلومات التواصل التي تظهر في لوحة التحكم وشاشة الدخول والتقارير.">
            <form method="POST" action="{{ route('admin.settings.identity') }}" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @php($identityLogo = public_path('storage/branding/logo.png'))
                    <div class="md:col-span-3 flex flex-col sm:flex-row sm:items-center gap-4 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 dark:bg-gray-800/50">
                        <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl overflow-hidden bg-white ring-1 ring-gray-200 dark:bg-gray-700 dark:ring-gray-600">
                            @if (file_exists($identityLogo))
                                <img id="identity-logo-preview" src="{{ asset('storage/branding/logo.png') }}" alt="الشعار الحالي" class="h-full w-full object-contain p-1">
                            @else
                                <span id="identity-logo-preview" class="flex items-center justify-center text-gray-300">
                                    <x-admin.icon name="settings" class="h-8 w-8" />
                                </span>
                            @endif
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">شعار المنصة</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">PNG أو JPG أو WebP بحجم أقصى 2MB، ويُستخدم الشعار في لوحة التحكم وشاشات الدخول ورؤوس التقارير.</p>
                            <input
                                type="file"
                                name="logo"
                                accept="image/png,image/jpeg,image/webp"
                                class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-wajhatak-600 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-wajhatak-700 dark:text-gray-400"
                                onchange="const f=this.files[0]; if(f){const r=new FileReader(); r.onload=e=>{const p=document.getElementById('identity-logo-preview'); p.outerHTML='<img id=\"identity-logo-preview\" src=\"'+e.target.result+'\" class=\"h-full w-full object-contain p-1\">'}; r.readAsDataURL(f);}"
                            >
                            @error('logo')
                                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <x-admin.input label="اسم المنصة" name="site_name" :value="old('site_name', \App\Models\Setting::get('site_name', 'وجهتك'))" required placeholder="وجهتك" />
                    </div>
                    <div>
                        <x-admin.input label="الشعار النصي (الوصف المختصر)" name="site_tagline" :value="old('site_tagline', \App\Models\Setting::get('site_tagline', 'وجهتك إلى العقار المناسب.'))" placeholder="وجهتك إلى العقار المناسب." />
                    </div>
                    <div>
                        <x-admin.input label="العنوان" name="address" :value="old('address', \App\Models\Setting::get('address', ''))" placeholder="صنعاء — اليمن" />
                    </div>
                    <div>
                        <x-admin.input label="هاتف الدعم" name="support_phone" :value="old('support_phone', \App\Models\Setting::get('support_phone', ''))" placeholder="+967 ..." />
                    </div>
                    <div>
                        <x-admin.input label="بريد الدعم" name="support_email" type="email" :value="old('support_email', \App\Models\Setting::get('support_email', ''))" placeholder="support@example.com" />
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">العملة الافتراضية</label>
                        <select name="default_currency" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-wajhatak-400 focus:ring-2 focus:ring-wajhatak-300/50 dark:bg-gray-800 dark:border-gray-600">
                            @foreach(['YER' => 'ريال يمني', 'SAR' => 'ريال سعودي', 'USD' => 'دولار أمريكي', 'AED' => 'درهم إماراتي'] as $code => $label)
                                <option value="{{ $code }}" @selected(\App\Models\Setting::get('default_currency', 'YER') === $code)>{{ $label }} ({{ $code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3 flex justify-end">
                        <x-admin.button type="submit">حفظ هوية المنصة</x-admin.button>
                    </div>
                </div>
            </form>
        </x-admin.card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- ====================== Settings list ====================== --}}
        <div class="lg:col-span-2">
            <x-admin.card :padding="false">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">إعدادات النظام</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">إدارة إعدادات منصة وجهتك</p>
                    </div>
                    <form method="GET" class="flex gap-2">
                        <x-admin.input name="search" value="{{ $search }}" placeholder="بحث بالمفتاح أو القيمة..." class="!w-56" />
                        <x-admin.button variant="secondary" type="submit">بحث</x-admin.button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">المفتاح (key)</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">القيمة</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">النوع</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($settings as $setting)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100 text-left" dir="ltr">{{ $setting->key }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300 max-w-xs truncate">
                                        @if($setting->type === 'boolean')
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $setting->value === '1' ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                                {{ $setting->value === '1' ? 'مفعل' : 'معطّل' }}
                                            </span>
                                        @else
                                            {{ $setting->value ?: '—' }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-700 px-2.5 py-0.5 text-xs font-semibold dark:bg-gray-800 dark:text-gray-300">{{ $types[$setting->type] ?? $setting->type }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <form method="PATCH" action="{{ route('admin.settings.update', $setting) }}" class="flex items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                @if($setting->type === 'boolean')
                                                    <input type="hidden" name="key" value="{{ $setting->key }}">
                                                    <input type="hidden" name="type" value="boolean">
                                                    <button
                                                        type="submit"
                                                        name="value"
                                                        value="{{ $setting->value === '1' ? '0' : '1' }}"
                                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition {{ $setting->value === '1' ? 'bg-wajhatak-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $setting->value === '1' ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                                    </button>
                                                @else
                                                    <input type="hidden" name="key" value="{{ $setting->key }}">
                                                    <a href="#" x-data x-on:click.prevent="$el.closest('tr').querySelector('[data-editor]').hidden = false" class="text-wajhatak-600 hover:text-wajhatak-700 text-sm font-medium">تعديل</a>
                                                @endif
                                            </form>
                                            <form method="POST" action="{{ route('admin.settings.destroy', $setting) }}" onsubmit="return confirm('حذف هذا الإعداد نهائيًا؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @if($setting->type !== 'boolean')
                                    <tr hidden data-editor class="bg-gray-50 dark:bg-gray-800/50">
                                        <td colspan="4" class="px-4 py-3">
                                            <form method="PATCH" action="{{ route('admin.settings.update', $setting) }}" class="flex flex-wrap items-end gap-3">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="key" value="{{ $setting->key }}">
                                                <div class="flex-1 min-w-[200px]">
                                                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">القيمة</label>
                                                    <input type="text" name="value" value="{{ $setting->value }}" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm focus:border-wajhatak-400 focus:ring-2 focus:ring-wajhatak-300/50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                                </div>
                                                <input type="hidden" name="type" value="{{ $setting->type }}">
                                                <x-admin.button type="submit">حفظ</x-admin.button>
                                                <button type="button" x-data x-on:click="$el.closest('tr').hidden = true" class="text-sm text-gray-500 hover:text-gray-700">إلغاء</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">لا توجد إعدادات.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>

        {{-- ====================== Add new setting ====================== --}}
        <div class="lg:sticky lg:top-20 self-start">
            <x-admin.card title="إضافة إعداد جديد" description="أضف مفتاح إعداد جديد للنظام">
                <form method="POST" action="{{ route('admin.settings.quick') }}">
                    @csrf
                    <x-admin.input label="المفتاح (key)" name="key" required placeholder="site_name" />
                    <div class="mt-4">
                        <x-admin.input label="القيمة" name="value" />
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">النوع</label>
                        <select name="type" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-wajhatak-400 focus:ring-2 focus:ring-wajhatak-300/50 dark:bg-gray-800 dark:border-gray-600">
                            @foreach($types as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-5">
                        <x-admin.button type="submit">إضافة الإعداد</x-admin.button>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
    </div>
</x-admin.layouts.admin>
