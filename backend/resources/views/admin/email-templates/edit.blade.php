<x-admin.layouts.admin heading="تعديل قالب البريد" title="تعديل قالب">
    <div class="max-w-7xl mx-auto space-y-6" x-data="{ 
        previewMode: 'html',
        showPreview: true,
        previewData: {
            name: 'أحمد محمد',
            code: '123456',
            ttl: '15',
            reason: 'سبب الرفض',
            property: 'شقة في الرياض',
            email: 'ahmed@example.com',
        },
        availableIcons: [
            { name: 'check-circle', svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>' },
            { name: 'x-circle', svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>' },
            { name: 'envelope', svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>' },
            { name: 'home', svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>' },
            { name: 'user', svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>' },
            { name: 'shield-check', svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>' },
        ],
        insertIcon(iconSvg) {
            const htmlEditor = document.getElementById('html_content');
            const iconHtml = `<span class="email-icon" style="display: inline-block; width: 24px; height: 24px; vertical-align: middle;">${iconSvg}</span>`;
            const start = htmlEditor.selectionStart;
            const end = htmlEditor.selectionEnd;
            const text = htmlEditor.value;
            htmlEditor.value = text.substring(0, start) + iconHtml + text.substring(end);
            htmlEditor.dispatchEvent(new Event('input'));
        },
        insertVariable(varName) {
            const htmlEditor = document.getElementById('html_content');
            const varHtml = `{{${varName}}}`;
            const start = htmlEditor.selectionStart;
            const end = htmlEditor.selectionEnd;
            const text = htmlEditor.value;
            htmlEditor.value = text.substring(0, start) + varHtml + text.substring(end);
            htmlEditor.dispatchEvent(new Event('input'));
        },
        async updatePreview() {
            const form = document.getElementById('templateForm');
            const formData = new FormData(form);
            formData.append('variables', JSON.stringify(this.previewData));
            
            try {
                const response = await fetch('{{ route('admin.email-templates.preview', $template) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const data = await response.json();
                
                if (this.previewMode === 'html' && data.html) {
                    document.getElementById('previewFrame').srcdoc = data.html;
                } else if (data.text) {
                    document.getElementById('previewFrame').srcdoc = `<pre style="direction: rtl; padding: 20px; font-family: monospace;">${data.text}</pre>`;
                }
            } catch (error) {
                console.error('Preview error:', error);
            }
        },
        init() {
            this.$watch('$el', () => {
                setTimeout(() => this.updatePreview(), 500);
            });
        }
    }" x-init="init()">
        <form method="POST" action="{{ route('admin.email-templates.update', $template) }}" id="templateForm">
            @csrf
            @method('PATCH')
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Editor Panel --}}
                <div class="space-y-6">
                    <x-admin.card title="معلومات القالب">
                        <div class="space-y-4">
                            @if(!$template->is_system)
                                <x-admin.input label="مفتاح القالب (Key)" name="key" :value="$template->key" placeholder="email_verification" />
                            @else
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">مفتاح القالب</label>
                                    <input type="text" value="{{ $template->key }}" disabled class="w-full rounded-xl border border-gray-200 bg-gray-100 px-3 py-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">
                                </div>
                            @endif
                            <x-admin.input label="اسم القالب" name="name" :value="$template->name" placeholder="رمز التحقق من البريد" />
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">الوصف</label>
                                <textarea name="description" rows="2" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800 dark:border-gray-600">{{ $template->description }}</textarea>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" @checked($template->is_active) class="h-4 w-4 accent-wajhatak-600">
                                <span class="text-sm font-bold text-gray-900 dark:text-gray-100">قالب نشط</span>
                            </label>
                        </div>
                    </x-admin.card>

                    <x-admin.card title="عنوان الرسالة">
                        <x-admin.input label="الموضوع" name="subject" :value="$template->subject" placeholder="رمز التحقق من وجهتك" />
                    </x-admin.card>

                    <x-admin.card title="محتوى HTML">
                        <div class="space-y-4">
                            <div class="flex gap-2 flex-wrap">
                                <button type="button" @click="insertVariable('name')" class="px-3 py-1.5 text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                                    {name}
                                </button>
                                <button type="button" @click="insertVariable('code')" class="px-3 py-1.5 text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                                    {code}
                                </button>
                                <button type="button" @click="insertVariable('ttl')" class="px-3 py-1.5 text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                                    {ttl}
                                </button>
                                <button type="button" @click="insertVariable('reason')" class="px-3 py-1.5 text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                                    {reason}
                                </button>
                                <button type="button" @click="insertVariable('property')" class="px-3 py-1.5 text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                                    {property}
                                </button>
                                <button type="button" @click="insertVariable('email')" class="px-3 py-1.5 text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                                    {email}
                                </button>
                            </div>
                            
                            <div class="relative">
                                <textarea 
                                    id="html_content" 
                                    name="html_content" 
                                    rows="20" 
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-mono leading-relaxed dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100"
                                    @input="updatePreview()"
                                >{{ old('html_content', $template->html_content) }}</textarea>
                            </div>
                            
                            <div class="flex gap-2 flex-wrap items-center">
                                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">أيقونات:</span>
                                @foreach($availableIcons as $icon)
                                    <button type="button" @click="insertIcon('{{ $icon['svg'] }}')" class="p-1.5 rounded bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="{{ $icon['name'] }}">
                                        <span class="w-5 h-5 text-gray-700 dark:text-gray-300">{!! $icon['svg'] !!}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </x-admin.card>

                    <x-admin.card title="محتوى نصي (Text Fallback)">
                        <textarea name="text_content" rows="5" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm leading-relaxed dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100">{{ old('text_content', $template->text_content) }}</textarea>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">يُستخدم للعملاء الذين لا يدعمون HTML</p>
                    </xadmin.card>

                    <div class="flex items-center gap-3">
                        <x-admin.button type="submit">حفظ التغييرات</x-admin.button>
                        <a href="{{ route('admin.email-templates.index') }}" class="px-4 py-2.5 text-sm font-bold text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 transition">إلغاء</a>
                    </div>
                </div>

                {{-- Preview Panel --}}
                <div class="space-y-6">
                    <x-admin.card title="معاينة حية">
                        <div class="space-y-4">
                            <div class="flex gap-2">
                                <button type="button" @click="previewMode = 'html'; updatePreview()" :class="previewMode === 'html' ? 'bg-wajhatak-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'" class="px-3 py-1.5 text-xs font-bold rounded transition">
                                    HTML
                                </button>
                                <button type="button" @click="previewMode = 'text'; updatePreview()" :class="previewMode === 'text' ? 'bg-wajhatak-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'" class="px-3 py-1.5 text-xs font-bold rounded transition">
                                    Text
                                </button>
                            </div>
                            
                            <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden" style="height: 600px;">
                                <iframe id="previewFrame" class="w-full h-full" sandbox="allow-same-origin"></iframe>
                            </div>
                            
                            <div class="space-y-2">
                                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-200">بيانات المعاينة:</h4>
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" x-model="previewData.name" @input="updatePreview()" placeholder="الاسم" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs dark:bg-gray-800 dark:border-gray-600">
                                    <input type="text" x-model="previewData.code" @input="updatePreview()" placeholder="الرمز" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs dark:bg-gray-800 dark:border-gray-600">
                                    <input type="text" x-model="previewData.ttl" @input="updatePreview()" placeholder="الصلاحية" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs dark:bg-gray-800 dark:border-gray-600">
                                    <input type="text" x-model="previewData.reason" @input="updatePreview()" placeholder="السبب" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs dark:bg-gray-800 dark:border-gray-600">
                                </div>
                            </div>
                        </div>
                    </xadmin.card>

                    <x-admin.card title="معلومات القالب">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">الإصدار:</span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ $template->version }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">نوع القالب:</span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ $template->is_system ? 'نظامي' : 'مخصص' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">آخر تحديث:</span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ $template->updated_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </x-admin.card>
                </div>
            </div>
        </form>
    </div>
</x-admin.layouts.admin>
