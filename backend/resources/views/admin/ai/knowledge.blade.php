<x-admin.layouts.admin :heading="'قاعدة معرفة المساعد'" :title="'المساعد الذكي — قاعدة المعرفة'" :breadcrumbs="[['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'المساعد الذكي', 'url' => route('admin.ai.index')]]">
    <div class="space-y-5">
        @include('admin.ai._nav')

        @if (session('status'))
            <div role="status" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-700 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-400">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400">
                <ul class="list-disc space-y-1 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $selectedRoles = old('roles', $editing?->roles ?? ['client']);
            $selectedRoles = is_array($selectedRoles) ? $selectedRoles : ['client'];
            $keywordText = old('keywords_text', implode('، ', $editing?->keywords ?? []));
        @endphp

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
            <x-admin.card :title="$editing ? 'تعديل مادة معرفة' : 'إضافة مادة معرفة'" :description="'أضف إجابة موثوقة عن استخدام المنصة. العقارات والأسعار والتوفر تبقى دائمًا من قاعدة بيانات العقارات الحية.'">
                <form method="POST"
                      action="{{ $editing ? route('admin.ai.knowledge.update', ['article' => $editing->id]) : route('admin.ai.knowledge.store') }}"
                      class="space-y-4">
                    @csrf
                    @if ($editing)
                        @method('PUT')
                    @endif

                    <div>
                        <label for="slug" class="mb-1.5 block text-sm font-bold text-gray-700 dark:text-gray-200">المعرّف التقني</label>
                        <input id="slug" name="slug" dir="ltr" value="{{ old('slug', $editing?->slug) }}"
                               required maxlength="120" pattern="[A-Za-z0-9_-]+"
                               class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-left text-sm dark:border-gray-700 dark:bg-gray-900"
                               placeholder="viewing-request">
                        <p class="mt-1 text-xs text-gray-500">أحرف إنجليزية وأرقام وشرطة فقط، ويجب أن يكون فريدًا.</p>
                    </div>

                    <div>
                        <label for="topic" class="mb-1.5 block text-sm font-bold text-gray-700 dark:text-gray-200">عنوان المادة</label>
                        <input id="topic" name="topic" value="{{ old('topic', $editing?->topic) }}"
                               required maxlength="160"
                               class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900"
                               placeholder="كيف أطلب معاينة؟">
                    </div>

                    <div>
                        <label for="content" class="mb-1.5 block text-sm font-bold text-gray-700 dark:text-gray-200">الإجابة الموثوقة</label>
                        <textarea id="content" name="content" required minlength="10" maxlength="12000" rows="5"
                                  class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm leading-7 dark:border-gray-700 dark:bg-gray-900"
                                  placeholder="اشرح الخطوات الفعلية التي يستطيع المستخدم تنفيذها داخل وجهتك...">{{ old('content', $editing?->content) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">من 10 إلى 12,000 حرف. اكتب الحقائق والإجراءات الحالية فقط.</p>
                    </div>

                    <div>
                        <label for="keywords_text" class="mb-1.5 block text-sm font-bold text-gray-700 dark:text-gray-200">الكلمات المفتاحية</label>
                        <textarea id="keywords_text" name="keywords_text" required maxlength="2000" rows="2"
                                  class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900"
                                  placeholder="معاينة، موعد، زيارة، حجز موعد">{{ $keywordText }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">افصل بين الكلمات بالفاصلة العربية أو الإنجليزية أو بسطر جديد. الحد الأقصى 20 كلمة.</p>
                    </div>

                    <fieldset class="space-y-2">
                        <legend class="mb-1 text-sm font-bold text-gray-700 dark:text-gray-200">الأدوار التي يمكنها استخدام المادة</legend>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach (['client' => 'المستخدم', 'agent' => 'الوكيل', 'admin' => 'الإدارة'] as $role => $label)
                                <label class="flex items-center gap-2 rounded-xl border border-gray-200 p-3 text-sm dark:border-gray-700">
                                    <input type="checkbox" name="roles[]" value="{{ $role }}" @checked(in_array($role, $selectedRoles, true)) class="h-4 w-4 accent-emerald-700">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="target_screen" class="mb-1.5 block text-sm font-bold text-gray-700 dark:text-gray-200">الشاشة المرتبطة (اختياري)</label>
                            <input id="target_screen" name="target_screen" dir="ltr" value="{{ old('target_screen', $editing?->target_screen) }}"
                                   maxlength="100" pattern="[A-Za-z][A-Za-z0-9_]*"
                                   class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-left text-sm dark:border-gray-700 dark:bg-gray-900"
                                   placeholder="PropertyDetailsScreen">
                        </div>
                        <div>
                            <label for="priority" class="mb-1.5 block text-sm font-bold text-gray-700 dark:text-gray-200">أولوية الترتيب</label>
                            <input id="priority" name="priority" type="number" min="0" max="10000" value="{{ old('priority', $editing?->priority ?? 100) }}"
                                   class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900">
                            <p class="mt-1 text-xs text-gray-500">الأرقام الأقل تظهر أولًا عند تساوي التطابق.</p>
                        </div>
                    </div>

                    <label class="flex items-center gap-3 rounded-xl border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $editing?->is_active ?? true)) class="h-4 w-4 accent-emerald-700">
                        <span class="font-bold">مفعّلة للمساعد</span>
                    </label>

                    <div class="flex flex-wrap gap-2 pt-1">
                        <button type="submit" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-extrabold text-white hover:bg-emerald-800">
                            {{ $editing ? 'حفظ النسخة الجديدة' : 'إضافة المادة' }}
                        </button>
                        @if ($editing)
                            <a href="{{ route('admin.ai.knowledge.index') }}" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-bold text-gray-700 dark:border-gray-700 dark:text-gray-200">إلغاء التعديل</a>
                        @endif
                    </div>
                </form>
            </x-admin.card>

            <x-admin.card :title="'المواد المحفوظة'" :description="'المواد النشطة تدخل في الاسترجاع بحسب الكلمات المفتاحية والدور. إيقاف المادة يحتفظ بسجلها ولا يحذفها.'">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="text-right text-xs font-extrabold text-gray-500">
                            <tr>
                                <th class="px-3 py-3">المادة</th>
                                <th class="px-3 py-3">الأدوار</th>
                                <th class="px-3 py-3">الحالة / النسخة</th>
                                <th class="px-3 py-3">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($articles as $article)
                                <tr class="align-top">
                                    <td class="max-w-xs px-3 py-3">
                                        <div class="font-extrabold text-gray-900 dark:text-gray-100">{{ $article->topic }}</div>
                                        <div dir="ltr" class="mt-1 text-left text-xs text-gray-500">{{ $article->slug }}</div>
                                        <p class="mt-2 line-clamp-3 text-xs leading-6 text-gray-600 dark:text-gray-300">{{ $article->content }}</p>
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            @foreach ((array) $article->keywords as $keyword)
                                                <span class="rounded-full bg-gray-100 px-2 py-1 text-[11px] text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $keyword }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-gray-600 dark:text-gray-300">{{ collect((array) $article->roles)->map(fn ($role) => ['client' => 'مستخدم', 'agent' => 'وكيل', 'admin' => 'إدارة'][$role] ?? $role)->implode('، ') }}</td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-bold {{ $article->is_active ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
                                            {{ $article->is_active ? 'مفعّلة' : 'متوقفة' }}
                                        </span>
                                        <div class="mt-1 text-xs text-gray-500">نسخة {{ $article->version }}</div>
                                        <div class="mt-1 text-xs text-gray-500">{{ $article->updated_at?->format('Y-m-d H:i') }}</div>
                                    </td>
                                    <td class="space-y-2 px-3 py-3">
                                        <a href="{{ route('admin.ai.knowledge.index', ['edit' => $article->id]) }}" class="inline-flex rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-bold dark:border-gray-700">تعديل</a>
                                        <form method="POST" action="{{ route('admin.ai.knowledge.status', ['article' => $article->id]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $article->is_active ? 0 : 1 }}">
                                            <button type="submit" class="inline-flex rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-bold dark:border-gray-700">
                                                {{ $article->is_active ? 'إيقاف' : 'تفعيل' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-10 text-center text-sm text-gray-500">لا توجد مواد مضافة بعد. المعرفة الافتراضية للمساعد تعمل دون الحاجة إلى إضافة مواد هنا.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $articles->links() }}</div>
            </x-admin.card>
        </div>
    </div>
</x-admin.layouts.admin>
