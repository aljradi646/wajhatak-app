<x-admin.layouts.admin heading="إنشاء قالب بريد جديد" title="إنشاء قالب">
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="rounded-2xl border border-wajhatak-200 bg-wajhatak-50 p-4 text-sm leading-6 text-gray-700 dark:border-wajhatak-900/40 dark:bg-wajhatak-900/10 dark:text-gray-200">
            أنشئ بيانات القالب أولًا، وبعد الحفظ يفتح <strong>استوديو القوالب</strong> مباشرة لتحريره بصريًا أو بالكود
            <strong>HTML / CSS</strong>، مع المعاينة والمتغيرات والحفظ التلقائي والإصدارات والنشر.
        </div>
        <form method="POST" action="{{ route('admin.email-templates.store') }}">
            @csrf
            
            <x-admin.card title="معلومات القالب">
                <div class="space-y-4">
                    <x-admin.input label="مفتاح القالب (Key)" name="key" placeholder="email_verification" required />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mr-2">معرف فريد للقالب (أحرف إنجليزية، شرطات سفلية)</p>
                    
                    <x-admin.input label="اسم القالب" name="name" placeholder="رمز التحقق من البريد" required />
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">الوصف</label>
                        <textarea name="description" rows="2" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800 dark:border-gray-600" placeholder="وصف قصير للقالب"></textarea>
                    </div>
                    
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 accent-wajhatak-600">
                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">قالب نشط</span>
                    </label>
                </div>
            </x-admin.card>

            <x-admin.card title="عنوان الرسالة">
                <x-admin.input label="الموضوع" name="subject" placeholder="رمز التحقق من وجهتك" required />
            </x-admin.card>

            <x-admin.card title="محتوى HTML">
                <div class="space-y-4">
                    <div class="flex gap-2 flex-wrap">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">متغيرات متاحة:</span>
                        <span class="px-2 py-1 text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded">{name}</span>
                        <span class="px-2 py-1 text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded">{code}</span>
                        <span class="px-2 py-1 text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded">{ttl}</span>
                        <span class="px-2 py-1 text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded">{reason}</span>
                        <span class="px-2 py-1 text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded">{property}</span>
                        <span class="px-2 py-1 text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded">{email}</span>
                    </div>
                    
                    <textarea 
                        name="html_content" 
                        rows="15" 
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-mono leading-relaxed dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100"
                        placeholder="<h1>مرحبا {name}</h1>"
                    ></textarea>
                    
                    <p class="text-xs text-gray-500 dark:text-gray-400">استخدم المتغيرات بين أقواس معقوفة: {name}</p>
                </div>
            </x-admin.card>

            <x-admin.card title="محتوى نصي (Text Fallback)">
                <textarea name="text_content" rows="5" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm leading-relaxed dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100" placeholder="مرحبا {name}، رمز التحقق الخاص بك هو: {code}"></textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">يُستخدم للعملاء الذين لا يدعمون HTML</p>
            </x-admin.card>

            <div class="flex items-center gap-3">
                <x-admin.button type="submit">إنشاء القالب</x-admin.button>
                <a href="{{ route('admin.email-templates.index') }}" class="px-4 py-2.5 text-sm font-bold text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 transition">إلغاء</a>
            </div>
        </form>
    </div>
</x-admin.layouts.admin>
