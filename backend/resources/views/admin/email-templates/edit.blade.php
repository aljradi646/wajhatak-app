@php
    $statusLabels = [
        'draft' => 'مسودة',
        'published' => 'منشور',
        'archived' => 'مؤرشف',
    ];
@endphp

<x-admin.layouts.admin heading="Email Template Studio" title="استوديو قوالب البريد">
    <div
        x-data="emailTemplateStudio()"
        x-init="init()"
        class="space-y-4"
    >
        <form id="templateForm" method="POST" action="{{ route('admin.email-templates.update', $template) }}" class="space-y-4">
            @csrf
            @method('PATCH')
            <input type="hidden" name="html_content" id="htmlContentField">
            <input type="hidden" name="css_styles" id="cssStylesField">
            <input type="hidden" name="variables" id="variablesField">
            <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="me-auto min-w-0">
                    <div class="text-lg font-black truncate">{{ $template->name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $template->key }} · v{{ $template->version }}</div>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200" x-text="statusLabel"></span>
                <span class="text-xs" :class="saveClass" x-text="saveStatus"></span>
                <a href="{{ route('admin.email-templates.history', $template) }}" class="rounded-lg border px-3 py-2 text-sm font-bold">السجل</a>
                <button type="button" @click="publish" :disabled="busy" class="rounded-lg bg-wajhatak-600 px-3 py-2 text-sm font-bold text-white disabled:opacity-50">نشر</button>
                @if(!$template->is_system)
                    <button type="button" @click="archive" :disabled="busy" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-bold text-red-700 disabled:opacity-50">أرشفة</button>
                @endif
                <button type="submit" :disabled="busy" class="rounded-lg bg-gray-900 px-3 py-2 text-sm font-bold text-white dark:bg-gray-100 dark:text-gray-900 disabled:opacity-50">حفظ إصدار</button>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.4fr)_minmax(360px,0.9fr)] gap-4">
                <section class="rounded-2xl border border-gray-200 bg-white overflow-hidden dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 px-3 py-2 dark:border-gray-700">
                        <button type="button" @click="mode='visual'; mountVisual()" :class="mode==='visual' ? 'bg-wajhatak-600 text-white' : 'bg-gray-100 dark:bg-gray-800'" class="rounded-lg px-3 py-2 text-sm font-bold">مرئي</button>
                        <button type="button" @click="mode='code'; mountCode()" :class="mode==='code' ? 'bg-wajhatak-600 text-white' : 'bg-gray-100 dark:bg-gray-800'" class="rounded-lg px-3 py-2 text-sm font-bold">HTML/CSS</button>
                        <button type="button" @click="insertVariablePicker" class="rounded-lg border px-3 py-2 text-sm font-bold">إدراج متغير</button>
                        <button type="button" @click="testEmail" class="ms-auto rounded-lg border border-wajhatak-200 px-3 py-2 text-sm font-bold text-wajhatak-700 dark:border-wajhatak-800 dark:text-wajhatak-300">إرسال تجريبي</button>
                    </div>

                    <div x-show="mode==='visual'" x-cloak class="min-h-[720px]">
                        <div id="gjs"></div>
                    </div>
                    <div x-show="mode==='code'" x-cloak class="grid grid-rows-[1fr_220px]">
                        <div id="monacoHtml" class="h-[520px] border-b border-gray-200 dark:border-gray-700"></div>
                        <div id="monacoCss" class="h-[220px]"></div>
                    </div>
                </section>

                <aside class="space-y-4">
                    <section class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="font-black">المعاينة</h2>
                            <div class="flex gap-1">
                                <button type="button" @click="device='desktop'; refreshPreview()" :class="device==='desktop' ? 'bg-gray-900 text-white' : 'bg-gray-100 dark:bg-gray-800'" class="rounded-lg px-2.5 py-1.5 text-xs font-bold">سطح المكتب</button>
                                <button type="button" @click="device='mobile'; refreshPreview()" :class="device==='mobile' ? 'bg-gray-900 text-white' : 'bg-gray-100 dark:bg-gray-800'" class="rounded-lg px-2.5 py-1.5 text-xs font-bold">الجوال</button>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-100 p-2 dark:border-gray-700 dark:bg-gray-950">
                            <iframe id="previewFrame" sandbox class="h-[620px] w-full rounded-lg bg-white" referrerpolicy="no-referrer"></iframe>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        <h2 class="font-black mb-3">المتغيرات</h2>
                        <div class="max-h-60 space-y-1 overflow-y-auto">
                            @foreach($variables as $key => $definition)
                                <button type="button" @click="insertVariable('{{ $key }}')" class="w-full text-right rounded-lg border px-2.5 py-2 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <div class="font-bold text-sm">&#123;&#123;{{ $key }}&#125;&#125;</div>
                                    <div class="text-[11px] text-gray-500">{{ $definition['description'] }}</div>
                                </button>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        <h2 class="font-black mb-3">بيانات المعاينة</h2>
                        <div class="grid grid-cols-1 gap-2">
                            @foreach($previewValues as $key => $value)
                                <label class="text-xs font-bold">
                                    {{ $key }}
                                    <input type="text" value="{{ is_scalar($value) ? $value : '' }}" @change="previewValues['{{ $key }}']=$event.target.value; refreshPreview()" class="mt-1 w-full rounded-lg border px-2.5 py-2 text-xs bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                                </label>
                            @endforeach
                        </div>
                    </section>
                </aside>
            </div>
        </form>

        <div x-show="showTest" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="showTest=false">
            <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl dark:bg-gray-900" @click.outside="showTest=false">
                <h2 class="text-lg font-black">إرسال بريد تجريبي</h2>
                <p class="mt-1 text-sm text-gray-500">سيتم استخدام SMTP/Resend المهيأ في النظام فعليًا.</p>
                <label class="mt-4 block text-sm font-bold">البريد المستلم</label>
                <input type="email" x-model="testRecipient" class="mt-1 w-full rounded-xl border px-3 py-2.5 bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" @click="showTest=false" class="rounded-lg border px-4 py-2 text-sm font-bold">إلغاء</button>
                    <button type="button" @click="sendTest" :disabled="busy" class="rounded-lg bg-wajhatak-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-50">إرسال الآن</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/grapesjs@0.23.6/dist/css/grapes.min.css">
        <script src="https://unpkg.com/grapesjs@0.23.6"></script>
        <script src="https://unpkg.com/grapesjs-preset-newsletter@1.0.2"></script>
        <script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.55.1/min/vs/loader.js"></script>
        <style>
            #gjs { min-height: 720px; }
            #gjs .gjs-cv-canvas { width: 100%; }
            .gjs-one-bg { background: transparent; }
        </style>
        <script>
            function emailTemplateStudio() {
                return {
                    mode: 'visual',
                    device: 'desktop',
                    busy: false,
                    saveStatus: 'Saved',
                    showTest: false,
                    testRecipient: '{{ auth()->user()->email }}',
                    testTimer: null,
                    previewValues: @json($previewValues),
                    visualEditor: null,
                    htmlEditor: null,
                    cssEditor: null,
                    initialHtml: @json($template->html_content ?? ''),
                    initialCss: @json(is_array($template->css_styles) ? implode("\n", $template->css_styles) : (string) ($template->css_styles ?? '')),
                    status: @json($template->status),
                    get statusLabel() { return @json($statusLabels) [this.status] || this.status; },
                    get saveClass() {
                        return this.saveStatus === 'Save failed' ? 'text-red-600' : 'text-gray-500';
                    },
                    async init() {
                        this.mountVisual();
                        await this.loadMonaco();
                        this.startAutosave();
                        this.refreshPreview();
                    },
                    mountVisual() {
                        if (this.visualEditor || !window.grapesjs) return;
                        this.visualEditor = grapesjs.init({
                            container: '#gjs',
                            height: '720px',
                            storageManager: false,
                            fromElement: false,
                            plugins: ['gjs-preset-newsletter'],
                            pluginsOpts: {
                                'gjs-preset-newsletter': {
                                    inlineCss: true,
                                    showBlocksOnLoad: true,
                                    updateStyleManager: true,
                                },
                            },
                            deviceManager: {
                                devices: [
                                    { name: 'Desktop', width: '100%' },
                                    { name: 'Mobile', width: '390px' },
                                ],
                            },
                            components: this.initialHtml || '<table role="presentation" width="100%"><tr><td style="padding:32px;text-align:center;"><h1>عنوان الرسالة</h1><p>اكتب محتوى الرسالة من هنا.</p></td></tr></table>',
                            style: this.initialCss,
                        });
                        this.visualEditor.on('component:update component:styleUpdate', () => this.markDirty());
                        this.visualEditor.on('storage:end:load', () => this.markDirty());
                    },
                    loadMonaco() {
                        return new Promise((resolve) => {
                            if (this.htmlEditor) return resolve();
                            require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.55.1/min/vs' }});
                            require(['vs/editor/editor.main'], () => {
                                this.htmlEditor = monaco.editor.create(document.getElementById('monacoHtml'), {
                                    value: this.initialHtml,
                                    language: 'html',
                                    automaticLayout: true,
                                    minimap: { enabled: false },
                                    wordWrap: 'on',
                                    tabSize: 2,
                                    formatOnPaste: true,
                                    formatOnType: true,
                                    suggest: { showWords: true },
                                });
                                this.cssEditor = monaco.editor.create(document.getElementById('monacoCss'), {
                                    value: this.initialCss,
                                    language: 'css',
                                    automaticLayout: true,
                                    minimap: { enabled: false },
                                    wordWrap: 'on',
                                    tabSize: 2,
                                });
                                const change = () => this.markDirty();
                                this.htmlEditor.onDidChangeModelContent(change);
                                this.cssEditor.onDidChangeModelContent(change);
                                resolve();
                            });
                        });
                    },
                    currentHtml() {
                        if (this.mode === 'code' && this.htmlEditor) return this.htmlEditor.getValue();
                        return this.visualEditor ? this.visualEditor.runCommand('gjs-get-inlined-html') || this.visualEditor.getHtml() : this.initialHtml;
                    },
                    currentCss() {
                        if (this.mode === 'code' && this.cssEditor) return this.cssEditor.getValue();
                        return this.visualEditor ? this.visualEditor.getCss() : this.initialCss;
                    },
                    syncCodeFromVisual() {
                        if (!this.visualEditor || !this.htmlEditor || !this.cssEditor) return;
                        this.htmlEditor.setValue(this.visualEditor.getHtml());
                        this.cssEditor.setValue(this.visualEditor.getCss());
                    },
                    syncVisualFromCode() {
                        if (!this.visualEditor || !this.htmlEditor || !this.cssEditor) return;
                        this.visualEditor.setComponents(this.htmlEditor.getValue());
                        this.visualEditor.setStyle(this.cssEditor.getValue());
                    },
                    mountCode() {
                        this.mode = 'code';
                        this.syncCodeFromVisual();
                    },
                    mountVisual() {
                        this.mode = 'visual';
                        if (!this.visualEditor) this._mountVisualOnce();
                        else if (this.htmlEditor) this.syncVisualFromCode();
                    },
                    _mountVisualOnce() {
                        if (this.visualEditor || !window.grapesjs) return;
                        this.visualEditor = grapesjs.init({
                            container: '#gjs',
                            height: '720px',
                            storageManager: false,
                            fromElement: false,
                            plugins: ['gjs-preset-newsletter'],
                            pluginsOpts: { 'gjs-preset-newsletter': { inlineCss: true, showBlocksOnLoad: true } },
                            components: this.initialHtml,
                            style: this.initialCss,
                        });
                        this.visualEditor.on('component:update component:styleUpdate', () => this.markDirty());
                    },
                    insertVariable(name) {
                        const token = '{{'+name+'}}';
                        if (this.mode === 'code' && this.htmlEditor) {
                            const editor = this.htmlEditor;
                            const selection = editor.getSelection();
                            editor.executeEdits('variable', [{
                                range: selection,
                                text: token,
                                forceMoveMarkers: true,
                            }]);
                            editor.focus();
                        } else if (this.visualEditor) {
                            const selected = this.visualEditor.getSelected();
                            if (selected) {
                                selected.components(token);
                            } else {
                                this.visualEditor.addComponents('<p>'+token+'</p>');
                            }
                            this.markDirty();
                        }
                        this.refreshPreview();
                    },
                    insertVariablePicker() {
                        const key = prompt('اكتب اسم المتغير من القائمة الظاهرة، مثل user.name');
                        if (key && @json(array_keys($variables)).includes(key)) this.insertVariable(key);
                    },
                    markDirty() {
                        this.saveStatus = 'Unsaved changes';
                        clearTimeout(this.testTimer);
                        this.testTimer = setTimeout(() => this.autosave(), 900);
                        this.refreshPreviewDebounced();
                    },
                    refreshPreviewDebounced() {
                        clearTimeout(this._previewTimer);
                        this._previewTimer = setTimeout(() => this.refreshPreview(), 200);
                    },
                    payload(includeMeta=true) {
                        const html = this.currentHtml();
                        const css = this.currentCss();
                        document.getElementById('htmlContentField').value = html;
                        document.getElementById('cssStylesField').value = JSON.stringify(css ? [css] : []);
                        document.getElementById('variablesField').value = JSON.stringify(@json(array_keys($variables)));
                        return {
                            name: @json($template->name),
                            subject: @json($template->subject),
                            html_content: html,
                            css_styles: css ? [css] : [],
                            text_content: @json($template->text_content),
                            preview_variables: this.previewValues,
                        };
                    },
                    async refreshPreview() {
                        const payload = this.payload(false);
                        try {
                            const response = await fetch('{{ route('admin.email-templates.preview', $template) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify(payload),
                            });
                            if (!response.ok) return;
                            const data = await response.json();
                            document.getElementById('previewFrame').style.height = this.device === 'mobile' ? '620px' : '620px';
                            document.getElementById('previewFrame').style.maxWidth = this.device === 'mobile' ? '390px' : '100%';
                            document.getElementById('previewFrame').srcdoc = data.html || '<div style="padding:24px;font-family:Arial">لا يوجد محتوى HTML</div>';
                        } catch (_) {}
                    },
                    async autosave() {
                        this.saveStatus = 'Saving...';
                        const html = this.currentHtml();
                        const css = this.currentCss();
                        try {
                            const response = await fetch('{{ route('admin.email-templates.autosave', $template) }}', {
                                method: 'POST',
                                headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                                body: JSON.stringify({
                                    name: @json($template->name),
                                    subject: @json($template->subject),
                                    description: @json($template->description),
                                    html_content: html,
                                    text_content: @json($template->text_content),
                                    css_styles: css ? [css] : [],
                                    variables: @json(array_keys($variables)),
                                }),
                            });
                            const data = await response.json();
                            if (!response.ok) throw new Error(data.message || 'save_failed');
                            this.status = data.status;
                            this.saveStatus = 'Saved';
                        } catch (_) {
                            this.saveStatus = 'Save failed';
                        }
                    },
                    beforeSubmit() {
                        this.payload();
                    },
                    async publish() {
                        if (this.busy) return;
                        this.busy = true;
                        await this.autosave();
                        try {
                            const response = await fetch('{{ route('admin.email-templates.publish', $template) }}', {
                                method: 'POST',
                                headers: {'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                            });
                            const data = await response.json();
                            if (!response.ok) throw new Error(data.message || 'publish_failed');
                            this.status = data.status;
                            this.saveStatus = 'Saved';
                            window.location.reload();
                        } catch (_) {
                            this.saveStatus = 'Save failed';
                        } finally { this.busy = false; }
                    },
                    async archive() {
                        if (!confirm('أرشفة القالب؟')) return;
                        this.busy = true;
                        try {
                            const response = await fetch('{{ route('admin.email-templates.archive', $template) }}', {method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}});
                            if (!response.ok) throw new Error('archive_failed');
                            this.status = 'archived';
                            window.location.reload();
                        } finally { this.busy = false; }
                    },
                    testEmail() { this.showTest = true; },
                    async sendTest() {
                        if (!this.testRecipient) return;
                        this.busy = true;
                        const data = this.payload(false);
                        data.recipient = this.testRecipient;
                        data.preview_variables = this.previewValues;
                        try {
                            const response = await fetch('{{ route('admin.email-templates.test-email', $template) }}', {
                                method: 'POST',
                                headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                                body: JSON.stringify(data),
                            });
                            const body = await response.json();
                            if (!response.ok) throw new Error(body.message || 'send_failed');
                            alert(body.message || 'تم إرسال البريد التجريبي.');
                            this.showTest = false;
                        } catch (e) {
                            alert(e.message || 'تعذر إرسال البريد التجريبي.');
                        } finally { this.busy = false; }
                    },
                    startAutosave() {}
                };
            }
        </script>
    @endpush
</x-admin.layouts.admin>
