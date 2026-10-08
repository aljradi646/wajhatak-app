@php
$statusLabels = ['draft' => 'مسودة', 'published' => 'منشور', 'archived' => 'مؤرشف'];
@endphp
<x-admin.layouts.admin heading="قوالب البريد الإلكتروني" title="قوالب البريد">
<div
    x-data="emailTemplateManager()"
    x-init="init()"
    class="mx-auto max-w-7xl space-y-6"
>
    <section class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col gap-4 md:flex-row md:items-center">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-wajhatak-50 text-wajhatak-700 dark:bg-wajhatak-900/30 dark:text-wajhatak-300">
                        <x-admin.icon name="mail" class="h-6 w-6" />
                    </span>
                    <div>
                        <h2 class="text-xl font-black">قوالب البريد الجاهزة</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">إدارة القوالب المنشورة والمسودات من مكان واحد. المحرر لا يُحمّل إلا عند فتحه.</p>
                    </div>
                </div>
            </div>
            <button type="button" @click="openCreate()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-wajhatak-600 px-5 py-3 text-sm font-black text-white shadow-sm hover:bg-wajhatak-700">
                <x-admin.icon name="plus" class="h-5 w-5" />
                إنشاء قالب
            </button>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($templates as $template)
            @php
                $status = $template->status ?: ($template->is_active ? 'published' : 'archived');
                $type = $template->template_type ?: 'custom';
                $typeLabel = $templateTypes[$type] ?? $type;
            @endphp
            <article class="group relative overflow-hidden rounded-3xl border border-gray-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-xl dark:border-gray-700 dark:bg-gray-900">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-l from-wajhatak-700 via-wajhatak-500 to-wajhatak-300 opacity-80"></div>
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        <x-admin.icon name="mail" class="h-6 w-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate font-black text-gray-900 dark:text-gray-100">{{ $template->name }}</h3>
                        <p class="mt-1 truncate font-mono text-[11px] text-gray-500">{{ $template->key }}</p>
                    </div>
                    <div class="pointer-events-none absolute left-4 top-4 flex translate-y-1 gap-1 opacity-0 transition duration-150 group-hover:pointer-events-auto group-hover:translate-y-0 group-hover:opacity-100">
                        <button type="button" @click="openEdit({{ $template->id }})" title="تعديل" class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/95 text-gray-700 shadow ring-1 ring-gray-200 hover:text-wajhatak-700 dark:bg-gray-800/95 dark:text-gray-200 dark:ring-gray-700">
                            <x-admin.icon name="edit" class="h-4 w-4" />
                        </button>
                        <a href="{{ route('admin.email-templates.history',$template) }}" title="السجل" class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/95 text-gray-700 shadow ring-1 ring-gray-200 hover:text-wajhatak-700 dark:bg-gray-800/95 dark:text-gray-200 dark:ring-gray-700">
                            <x-admin.icon name="activity" class="h-4 w-4" />
                        </a>
                        @if(!$template->is_system)
                            <form method="POST" action="{{ route('admin.email-templates.duplicate',$template) }}" class="contents">
                                @csrf
                                <button title="نسخ" class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/95 text-gray-700 shadow ring-1 ring-gray-200 hover:text-wajhatak-700 dark:bg-gray-800/95 dark:text-gray-200 dark:ring-gray-700">
                                    <x-admin.icon name="copy" class="h-4 w-4" />
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.email-templates.destroy',$template) }}" class="contents" onsubmit="return confirm('حذف القالب نهائيًا؟')">
                                @csrf @method('DELETE')
                                <button title="حذف" class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/95 text-red-600 shadow ring-1 ring-red-100 hover:bg-red-50 dark:bg-gray-800/95 dark:ring-red-900/40">
                                    <x-admin.icon name="trash" class="h-4 w-4" />
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="rounded-full bg-wajhatak-50 px-2.5 py-1 text-[11px] font-black text-wajhatak-700 dark:bg-wajhatak-900/30 dark:text-wajhatak-300">{{ $typeLabel }}</span>
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $status === 'published' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : ($status === 'archived' ? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300') }}">{{ $statusLabels[$status] ?? $status }}</span>
                    @if($template->is_system)<span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-black text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">نظامي</span>@endif
                </div>

                <p class="mt-4 min-h-[48px] line-clamp-2 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $template->description ?: 'لا يوجد وصف لهذا القالب.' }}</p>

                <div class="mt-5 grid grid-cols-2 gap-3 rounded-2xl bg-gray-50 p-3 text-xs dark:bg-gray-800/70">
                    <div><div class="text-gray-500">الإصدار الحالي</div><div class="mt-1 font-black">v{{ $template->version }}</div></div>
                    <div><div class="text-gray-500">الإصدار المنشور</div><div class="mt-1 font-black">{{ $template->published_version ? 'v'.$template->published_version : '—' }}</div></div>
                </div>

                <button type="button" @click="openEdit({{ $template->id }})" class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-black hover:border-wajhatak-500 hover:text-wajhatak-700 dark:border-gray-700 dark:hover:border-wajhatak-500 dark:hover:text-wajhatak-300">
                    فتح المحرر
                    <x-admin.icon name="chevron-left" class="h-4 w-4" />
                </button>
            </article>
        @empty
            <div class="col-span-full rounded-3xl border border-dashed border-gray-300 p-12 text-center dark:border-gray-700">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800"><x-admin.icon name="mail" class="h-7 w-7 text-gray-400" /></div>
                <h3 class="mt-4 font-black">لا توجد قوالب بعد</h3>
                <p class="mt-1 text-sm text-gray-500">أنشئ أول قالب بريد من الزر أعلاه.</p>
            </div>
        @endforelse
    </div>

    {{ $templates->links() }}

    {{-- Editor modal: deliberately not dismissible by outside click. --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-950/70 p-2 backdrop-blur-sm sm:p-4" @keydown.escape.prevent="close()" x-transition.opacity>
        <section role="dialog" aria-modal="true" aria-labelledby="emailEditorTitle" class="flex h-[92vh] w-[96vw] flex-col overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-950 sm:h-[80vh] sm:w-[80vw]" @click.stop>
            <header class="flex shrink-0 items-center gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-wajhatak-600 text-white"><x-admin.icon name="code" class="h-5 w-5" /></div>
                <div class="min-w-0 flex-1">
                    <h2 id="emailEditorTitle" class="truncate text-base font-black" x-text="editing ? 'تعديل قالب البريد' : 'إنشاء قالب بريد'"></h2>
                    <p class="text-[11px] text-gray-500" x-text="editorReady ? 'Monaco Code Editor · HTML/CSS' : 'تهيئة محرر الكود…'"></p>
                </div>
                <span class="hidden rounded-full bg-gray-100 px-3 py-1 text-[11px] font-black dark:bg-gray-800 sm:inline-flex" x-text="saveState"></span>
                <button type="button" @click="close()" title="إغلاق" class="flex h-9 w-9 items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"><x-admin.icon name="close" class="h-5 w-5" /></button>
            </header>

            <div class="min-h-0 flex-1 grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_330px]">
                <main class="min-h-0 flex flex-col bg-[#111817]">
                    <div class="flex shrink-0 items-center gap-1 border-b border-white/10 bg-[#0d1312] px-3 py-2">
                        <button type="button" @click="setPane('html')" :class="pane==='html' ? 'bg-white/10 text-white' : 'text-white/55'" class="rounded-lg px-3 py-2 text-xs font-black">HTML</button>
                        <button type="button" @click="setPane('css')" :class="pane==='css' ? 'bg-white/10 text-white' : 'text-white/55'" class="rounded-lg px-3 py-2 text-xs font-black">CSS</button>
                        <span class="mx-2 h-5 w-px bg-white/10"></span>
                        <button type="button" @click="insertVariablePrompt()" class="rounded-lg px-3 py-2 text-xs font-bold text-white/70 hover:bg-white/10">متغير</button>
                        <button type="button" @click="showIcons=true" class="rounded-lg px-3 py-2 text-xs font-bold text-white/70 hover:bg-white/10">أيقونة</button>
                        <button type="button" @click="$refs.asset.click()" class="rounded-lg px-3 py-2 text-xs font-bold text-white/70 hover:bg-white/10">صورة</button>
                        <input x-ref="asset" type="file" accept="image/png,image/jpeg,image/gif,image/webp" class="hidden" @change="uploadAsset($event)">
                        <span class="ms-auto text-[10px] text-white/40">Ctrl/Cmd + S للحفظ</span>
                    </div>
                    <div class="min-h-0 flex-1 relative">
                        <div id="emailMonaco" class="absolute inset-0"></div>
                        <div x-show="!editorReady" class="absolute inset-0 flex items-center justify-center bg-[#111817] text-sm text-white/50">جارٍ تحميل محرر الكود…</div>
                    </div>
                </main>

                <aside class="min-h-0 overflow-y-auto border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900 xl:border-t-0 xl:border-s">
                    <div class="space-y-4 p-4">
                        <section>
                            <label class="mb-1.5 block text-xs font-black text-gray-500">اسم القالب</label>
                            <input x-model="form.name" maxlength="190" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-bold dark:border-gray-700 dark:bg-gray-800">
                        </section>
                        <section>
                            <label class="mb-1.5 block text-xs font-black text-gray-500">المفتاح البرمجي</label>
                            <input x-model="form.key" :disabled="editing && form.is_system" maxlength="100" pattern="[A-Za-z0-9._-]+" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 font-mono text-xs dark:border-gray-700 dark:bg-gray-800 disabled:opacity-60">
                            <p class="mt-1 text-[10px] text-gray-500">يُستخدم لربط القالب مع خدمة الإرسال الفعلية.</p>
                        </section>
                        <section>
                            <label class="mb-1.5 block text-xs font-black text-gray-500">نوع القالب</label>
                            <select x-model="form.template_type" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                                @foreach($templateTypes as $type=>$label)<option value="{{ $type }}">{{ $label }}</option>@endforeach
                            </select>
                        </section>
                        <section>
                            <label class="mb-1.5 block text-xs font-black text-gray-500">موضوع الرسالة</label>
                            <input x-model="form.subject" maxlength="190" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        </section>
                        <section>
                            <label class="mb-1.5 block text-xs font-black text-gray-500">الوصف</label>
                            <textarea x-model="form.description" rows="2" maxlength="5000" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800"></textarea>
                        </section>
                        <section>
                            <label class="mb-1.5 block text-xs font-black text-gray-500">النص البديل Plain Text</label>
                            <textarea x-model="form.text_content" rows="5" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 font-mono text-xs dark:border-gray-700 dark:bg-gray-800"></textarea>
                        </section>
                        <section>
                            <div class="mb-2 flex items-center justify-between"><h3 class="text-xs font-black">المتغيرات</h3><span class="text-[10px] text-gray-400" x-text="(form.variables||[]).length"></span></div>
                            <div class="max-h-44 space-y-1 overflow-y-auto">
                                @foreach($variableRegistry as $key=>$definition)
                                    <button type="button" @click="insertVariable('{{ $key }}')" class="w-full rounded-lg border border-gray-200 px-2 py-2 text-right hover:border-wajhatak-400 dark:border-gray-700">
                                        <div class="font-mono text-[11px] font-black">&#123;&#123;{{ $key }}&#125;&#125;</div>
                                        <div class="text-[10px] text-gray-500">{{ $definition['description'] }}</div>
                                    </button>
                                @endforeach
                            </div>
                        </section>
                        <section class="rounded-2xl bg-gray-50 p-3 dark:bg-gray-800">
                            <div class="flex items-center justify-between text-xs"><span class="text-gray-500">الحالة</span><span class="font-black" x-text="form.status || 'مسودة'"></span></div>
                            <div class="mt-1 flex items-center justify-between text-xs"><span class="text-gray-500">الإصدار</span><span class="font-black" x-text="'v'+(form.version || 1)"></span></div>
                        </section>
                    </div>
                </aside>
            </div>

            <footer class="flex shrink-0 flex-wrap items-center gap-2 border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                <button type="button" @click="preview()" :disabled="busy" class="rounded-xl border border-gray-200 px-3 py-2 text-xs font-black hover:border-wajhatak-500 dark:border-gray-700">تحديث المعاينة</button>
                <button type="button" @click="publish()" x-show="editing && form.status !== 'published'" :disabled="busy" class="rounded-xl bg-wajhatak-600 px-3 py-2 text-xs font-black text-white disabled:opacity-50">نشر</button>
                <button type="button" @click="openTest()" x-show="editing" class="rounded-xl border border-wajhatak-200 px-3 py-2 text-xs font-black text-wajhatak-700 dark:text-wajhatak-300">إرسال تجريبي</button>
                <span class="me-auto hidden text-[11px] text-gray-500 sm:block" x-text="errorMessage"></span>
                <button type="button" @click="close()" class="rounded-xl border px-4 py-2 text-xs font-black">إلغاء</button>
                <button type="button" @click="save()" :disabled="busy || !editorReady" class="rounded-xl bg-gray-900 px-5 py-2 text-xs font-black text-white disabled:opacity-50 dark:bg-gray-100 dark:text-gray-900">حفظ القالب</button>
            </footer>
        </section>
    </div>

    <div x-show="showPreview" x-cloak class="fixed inset-0 z-[120] flex items-center justify-center bg-black/60 p-3" @click.stop>
        <section class="flex h-[86vh] w-[92vw] max-w-5xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-gray-900" @click.stop>
            <header class="flex items-center gap-3 border-b p-4 dark:border-gray-700">
                <div class="font-black">المعاينة الآمنة</div>
                <button type="button" @click="showPreview=false" class="ms-auto rounded-xl border px-3 py-2 text-xs font-black">إغلاق</button>
            </header>
            <iframe id="emailPreviewFrame" title="معاينة البريد" sandbox referrerpolicy="no-referrer" class="min-h-0 flex-1 bg-gray-100"></iframe>
        </section>
    </div>

    <div x-show="showIcons" x-cloak class="fixed inset-0 z-[130] flex items-center justify-center bg-black/60 p-4" @click.stop>
        <section class="w-full max-w-2xl rounded-3xl bg-white p-5 shadow-2xl dark:bg-gray-900" @click.stop>
            <div class="flex items-center"><h3 class="font-black">مكتبة الأيقونات</h3><button type="button" @click="showIcons=false" class="ms-auto rounded-xl border px-3 py-2 text-xs font-black">إغلاق</button></div>
            <div class="mt-4 grid grid-cols-5 gap-2 sm:grid-cols-8">
                @foreach(["✓","✕","★","☆","♥","♡","→","←","☎","✉","⌂","⚡","🔒","🔔","🏠","📍","📅","🕐","💬","⭐","🏷️","🔗","👤","⚙️","🎉","⚠️","ℹ️"] as $icon)
                    <button type="button" @click="insertIcon(@js($icon));showIcons=false" class="flex h-14 items-center justify-center rounded-xl border text-2xl hover:border-wajhatak-500 dark:border-gray-700">{{ $icon }}</button>
                @endforeach
            </div>
        </section>
    </div>

    <div x-show="showTest" x-cloak class="fixed inset-0 z-[130] flex items-center justify-center bg-black/60 p-4" @click.stop>
        <section class="w-full max-w-md rounded-3xl bg-white p-5 shadow-2xl dark:bg-gray-900" @click.stop>
            <h3 class="font-black">إرسال نسخة تجريبية</h3>
            <p class="mt-1 text-xs text-gray-500">سيُستخدم نفس renderer والتعقيم المستخدم في الإرسال الفعلي.</p>
            <input x-model="testRecipient" type="email" placeholder="you@example.com" class="mt-4 w-full rounded-xl border bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800">
            <div class="mt-4 flex justify-end gap-2"><button @click="showTest=false" class="rounded-xl border px-3 py-2 text-xs font-black">إلغاء</button><button @click="sendTest()" :disabled="busy" class="rounded-xl bg-wajhatak-600 px-4 py-2 text-xs font-black text-white">إرسال</button></div>
        </section>
    </div>
</div>

@push('scripts')
<script>
function emailTemplateManager() {
    return {
        open: false, editing: false, editorReady: false, busy: false, pane: 'html',
        showIcons: false, showPreview: false, showTest: false, testRecipient: '',
        saveState: 'جاهز', errorMessage: '', previewTimer: null, monacoLoaded: false, editor: null,
        form: { id:null, name:'', key:'', template_type:'custom', subject:'', description:'', html_content:'', css_styles:[], text_content:'', variables:@json(array_keys($variableRegistry)), is_system:false, version:1, status:'draft' },

        init() {
            window.addEventListener('keydown', e => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's' && this.open) { e.preventDefault(); this.save(); }
            });
            const qs=new URLSearchParams(location.search); if(qs.get('create')==='1') this.openCreate(); else if(qs.get('edit')) this.openEdit(Number(qs.get('edit')));
        },

        blank() {
            return {
                id:null, name:'', key:'', template_type:'custom', subject:'', description:'',
                html_content:'<!doctype html>\n<html dir="rtl" lang="ar">\n<head>\n  <meta charset="UTF-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>وجهتك</title>\n</head>\n<body style="margin:0;padding:0;background:#f4f7f6;font-family:Arial,sans-serif;direction:rtl;">\n  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f6;">\n    <tr><td align="center" style="padding:32px 16px;">\n      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;">\n        <tr><td style="padding:32px;">\n          <h1 style="margin:0 0 16px;color:#075E4A;">عنوان الرسالة</h1>\n          <p style="margin:0;color:#374151;">اكتب محتوى الرسالة هنا.</p>\n        </td></tr>\n      </table>\n    </td></tr>\n  </table>\n</body>\n</html>', css_styles:[], text_content:'', variables:@json(array_keys($variableRegistry)), is_system:false, version:1, status:'draft'
            };
        },

        async openCreate() {
            this.editing=false; this.form=this.blank(); this.errorMessage=''; this.saveState='مسودة جديدة'; this.open=true;
            await this.ensureMonaco(); this.loadEditorValue(); this.refreshPreviewDebounced();
        },

        async openEdit(id) {
            this.busy=true; this.errorMessage=''; this.saveState='جارٍ تحميل القالب…'; this.open=true;
            try {
                const r=await fetch(@json(url('/admin/email-templates')).+'/'+id+'/editor-data',{headers:{Accept:'application/json'},credentials:'same-origin'});
                const d=await r.json(); if(!r.ok) throw new Error(d.message||'تعذر تحميل القالب');
                this.form=d.data; this.editing=true;
                await this.ensureMonaco(); this.loadEditorValue(); this.saveState='تم التحميل'; this.refreshPreviewDebounced();
            } catch(e) { this.errorMessage=e.message; } finally { this.busy=false; }
        },

        close() {
            if(this.busy) return;
            this.open=false; this.showPreview=false; this.showIcons=false; this.showTest=false;
            this.editorReady=false; this.disposeEditor(); this.monacoLoaded=false;
            history.replaceState({},'',location.pathname);
        },

        async ensureMonaco() {
            if(this.monacoLoaded && window.monaco) { this.editorReady=true; return; }
            await new Promise((resolve,reject)=>{
                const existing=document.querySelector('script[data-wajhatak-monaco]');
                if(existing) { const t=setInterval(()=>{ if(window.monaco){clearInterval(t);resolve();}},50); setTimeout(()=>{clearInterval(t);reject(new Error('تعذر تحميل محرر الكود'));},15000); return; }
                const script=document.createElement('script'); script.dataset.wajhatakMonaco='1'; script.src='https://cdn.jsdelivr.net/npm/monaco-editor@0.57.0/min/vs/loader.js'; script.onload=()=>{
                    require.config({paths:{vs:'https://cdn.jsdelivr.net/npm/monaco-editor@0.57.0/min/vs'}});
                    require(['vs/editor/editor.main'],()=>resolve(),reject);
                }; script.onerror=()=>reject(new Error('تعذر تحميل محرر الكود.')); document.head.appendChild(script);
            });
            this.monacoLoaded=true; this.editorReady=true;
        },

        loadEditorValue() {
            if(!window.monaco) return;
            this.disposeEditor();
            const css=Array.isArray(this.form.css_styles)?this.form.css_styles.join('\n'):String(this.form.css_styles||'');
            this.editor=monaco.editor.create(document.getElementById('emailMonaco'),{
                value:this.form.html_content||'', language:'html', theme:document.documentElement.classList.contains('dark')?'vs-dark':'vs',
                automaticLayout:true, minimap:{enabled:true}, fontSize:13, lineNumbers:'on', wordWrap:'on',
                padding:{top:14,bottom:14}, tabSize:2, formatOnPaste:true, smoothScrolling:true,
                bracketPairColorization:{enabled:true}, guides:{bracketPairs:true}
            });
            this._cssValue=css;
            this.editor.onDidChangeModelContent(()=>{if(this.pane==='html') {this.form.html_content=this.editor.getValue();this.refreshPreviewDebounced();}});
        },

        disposeEditor(){ if(this.editor){this.editor.dispose();this.editor=null;} },

        setPane(pane) {
            if(this.pane===pane) return;
            if(this.editor) { if(this.pane==='html') this.form.html_content=this.editor.getValue(); else { this._cssValue=this.editor.getValue(); this.form.css_styles=[this._cssValue]; } }
            this.pane=pane;
            const value=pane==='html'?this.form.html_content:(this._cssValue||'');
            const oldModel=this.editor?.getModel();
            const model=monaco.editor.createModel(value,pane==='html'?'html':'css');
            this.editor?.setModel(model);
            oldModel?.dispose();
            this.editor?.layout();
            this.refreshPreviewDebounced();
        },

        currentCss(){ return this.pane==='css' && this.editor ? [this.editor.getValue()] : (Array.isArray(this.form.css_styles)?this.form.css_styles:[this._cssValue||'']); },
        currentHtml(){ return this.pane==='html' && this.editor ? this.editor.getValue() : this.form.html_content; },

        formPayload() {
            if(this.editor){ if(this.pane==='html') this.form.html_content=this.editor.getValue(); else this._cssValue=this.editor.getValue(); }
            this.form.css_styles=this.currentCss();
            return {...this.form, preview_variables:{}};
        },

        refreshPreviewDebounced(){ clearTimeout(this.previewTimer); this.previewTimer=setTimeout(()=>this.preview(),350); },

        async preview() {
            if(!this.open || !this.editorReady) return;
            const payload=this.formPayload();
            try {
                const r=await fetch(@json(route('admin.email-templates.preview-draft')),{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:JSON.stringify(payload)});
                const d=await r.json(); if(!r.ok) return;
                const f=document.getElementById('emailPreviewFrame'); f.srcdoc=d.html||'';
            } catch(e) {}
        },

        async save() {
            if(this.busy || !this.editorReady) return;
            this.busy=true; this.errorMessage=''; this.saveState='جارٍ الحفظ…';
            const payload=this.formPayload();
            const url=this.editing ? @json(url('/admin/email-templates')).+'/'+this.form.id : @json(route('admin.email-templates.store'));
            try {
                const r=await fetch(url,{method:this.editing?'PATCH':'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:JSON.stringify(payload)});
                const d=await r.json();
                if(!r.ok) throw new Error(Object.values(d.errors||{}).flat()[0]||d.message||'تعذر حفظ القالب');
                this.editing=true; this.form.id=d.data?.id||this.form.id; this.form.version=d.data?.version||this.form.version; this.form.status=d.data?.status||'draft';
                this.saveState='تم الحفظ'; this.form.key=d.data?.key||this.form.key;
                if(!this.form.id) location.reload();
            } catch(e){this.errorMessage=e.message;this.saveState='فشل الحفظ';} finally{this.busy=false;}
        },

        async publish() {
            if(!this.editing) return;
            this.busy=true; this.saveState='جارٍ النشر…';
            try {
                const r=await fetch(@json(url('/admin/email-templates'))+'/'+this.form.id+'/publish',{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''}});
                const d=await r.json(); if(!r.ok) throw new Error(d.message||'تعذر النشر');
                this.form.status='published'; this.form.version=d.version||this.form.version; this.saveState='تم النشر';
            } catch(e){this.errorMessage=e.message;this.saveState='فشل النشر';} finally{this.busy=false;}
        },

        insertVariable(name) {
            const token='{{'+name+'}}';
            if(this.editor){const s=this.editor.getSelection();this.editor.executeEdits('insert-variable',[{range:s,text:token,forceMoveMarkers:true}]);this.editor.focus();}
        },
        insertVariablePrompt(){const key=prompt('اسم المتغير، مثل user.name أو property.price');if(key&&@json(array_keys($variableRegistry)).includes(key))this.insertVariable(key);},
        insertIcon(icon){this.insertRaw('<span style="font-size:24px;line-height:1;display:inline-block">'+icon+'</span>');},
        insertRaw(text){if(!this.editor)return;const s=this.editor.getSelection();this.editor.executeEdits('insert',[{range:s,text,forceMoveMarkers:true}]);this.editor.focus();},
        async uploadAsset(event){const file=event.target.files?.[0];event.target.value='';if(!file)return;const fd=new FormData();fd.append('file',file);try{const r=await fetch(@json(route('admin.email-templates.assets')),{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:fd});const d=await r.json();if(!r.ok)throw new Error(d.message||'تعذر رفع الصورة');this.insertRaw('<img src="'+d.data[0].src+'" alt="'+file.name.replace(/"/g,'&quot;')+'" style="max-width:100%;height:auto;display:block;">');}catch(e){this.errorMessage=e.message;}},
        openTest(){this.testRecipient='';this.showTest=true;},
        async sendTest(){if(!this.form.id)return;this.busy=true;try{const r=await fetch(@json(url('/admin/email-templates'))+'/'+this.form.id+'/test-email',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:JSON.stringify({recipient:this.testRecipient,...this.formPayload()})});const d=await r.json();if(!r.ok)throw new Error(d.message||'تعذر الإرسال');this.showTest=false;this.saveState='تم إرسال النسخة التجريبية';}catch(e){this.errorMessage=e.message;}finally{this.busy=false;}}
    };
}
</script>
@endpush
</x-admin.layouts.admin>