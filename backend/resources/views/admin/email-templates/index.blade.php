@php
$statusLabels = ['draft' => 'مسودة', 'published' => 'منشور', 'archived' => 'مؤرشف'];
$previewValues = \App\Services\Mail\EmailTemplateVariableRegistry::previewValues();
@endphp

<link rel="stylesheet" href="https://unpkg.com/grapesjs@0.23.6/dist/css/grapes.min.css">
<style>
.email-studio-gjs{height:100%;min-height:620px}
.gjs-one-bg{background-color:#0f1715}.gjs-two-color{color:#b9cbc5}.gjs-three-bg{background-color:#17221f}.gjs-four-color,.gjs-four-color-h:hover{color:#72d9b8}
.gjs-cv-canvas{top:0;width:100%;height:100%;background:#dfe8e4}
.gjs-frame{background:#fff}
.email-code-editor{height:100%;width:100%}
.CodeMirror{height:100%;font-family:"JetBrains Mono","Fira Code",Consolas,monospace;font-size:13px;line-height:1.7}
</style>

<x-admin.layouts.admin heading="قوالب البريد الإلكتروني" title="استوديو قوالب البريد">
<div x-data="emailTemplateManager()" x-init="init()" class="mx-auto max-w-[1600px] space-y-6">
    <section class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-wajhatak-50 text-wajhatak-700 dark:bg-wajhatak-900/30 dark:text-wajhatak-300">
                    <x-admin.icon name="mail" class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <h2 class="text-xl font-black">قوالب البريد الإنتاجية</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">كل قالب محفوظ في قاعدة البيانات، مع محرر مرئي حقيقي، وضع HTML/CSS، معاينة، نسخ، إصدارات ونشر.</p>
                </div>
            </div>
            <a href="{{ route('admin.mail.index') }}" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-black dark:border-gray-700">إعدادات البريد</a>
            <button type="button" @click="openCreate()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-wajhatak-600 px-5 py-3 text-sm font-black text-white shadow-sm hover:bg-wajhatak-700">
                <x-admin.icon name="plus" class="h-5 w-5" /> إنشاء قالب
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
            <article class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="absolute inset-x-0 top-0 h-1 bg-wajhatak-600"></div>
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300"><x-admin.icon name="mail" class="h-6 w-6" /></div>
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate font-black">{{ $template->name }}</h3>
                        <div class="mt-1 truncate font-mono text-[11px] text-gray-500">{{ $template->key }}</div>
                    </div>
                    @if($template->is_system)
                        <span class="rounded-full bg-blue-50 px-2 py-1 text-[10px] font-black text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">نظامي</span>
                    @endif
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="rounded-full bg-wajhatak-50 px-2.5 py-1 text-[11px] font-black text-wajhatak-700 dark:bg-wajhatak-900/30 dark:text-wajhatak-300">{{ $typeLabel }}</span>
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $status === 'published' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : ($status === 'archived' ? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300') }}">{{ $statusLabels[$status] ?? $status }}</span>
                </div>
                <p class="mt-4 min-h-[52px] line-clamp-2 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $template->description ?: 'قالب بريد قابل للتحرير.' }}</p>
                <div class="mt-4 grid grid-cols-2 gap-3 rounded-2xl bg-gray-50 p-3 text-xs dark:bg-gray-800/70">
                    <div><div class="text-gray-500">الإصدار</div><div class="mt-1 font-black">v{{ $template->version }}</div></div>
                    <div><div class="text-gray-500">المنشور</div><div class="mt-1 font-black">{{ $template->published_version ? 'v'.$template->published_version : '—' }}</div></div>
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="button" @click="openEdit({{ $template->id }})" class="flex-1 rounded-xl bg-gray-900 px-3 py-2.5 text-xs font-black text-white dark:bg-gray-100 dark:text-gray-900">فتح الاستوديو</button>
                    <a href="{{ route('admin.email-templates.history', $template) }}" class="rounded-xl border px-3 py-2.5 text-xs font-black dark:border-gray-700">السجل</a>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-3xl border border-dashed border-gray-300 p-12 text-center dark:border-gray-700">
                <h3 class="font-black">لا توجد قوالب في قاعدة البيانات</h3>
                <p class="mt-1 text-sm text-gray-500">شغّل EmailTemplateSeeder أو أنشئ قالبًا جديدًا.</p>
            </div>
        @endforelse
    </div>

    {{ $templates->links() }}

    <div x-show="open" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-950/75 p-2 sm:p-4" @keydown.escape.prevent="close()" x-transition.opacity>
        <section role="dialog" aria-modal="true" aria-labelledby="emailEditorTitle" class="flex h-[96vh] w-[98vw] flex-col overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-950 xl:h-[92vh] xl:w-[96vw]" @click.stop>
            <header class="flex shrink-0 items-center gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-wajhatak-600 text-white"><x-admin.icon name="mail" class="h-5 w-5" /></div>
                <div class="min-w-0 flex-1">
                    <h2 id="emailEditorTitle" class="truncate text-base font-black" x-text="editing ? form.name : 'إنشاء قالب بريد جديد'"></h2>
                    <p class="text-[11px] text-gray-500" x-text="editorReady ? (mode==='visual' ? 'محرر بصري للنشرات البريدية' : 'تحرير المصدر HTML/CSS') : 'جارٍ تحميل محرر البريد…'"></p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-black dark:bg-gray-800" x-text="saveState"></span>
                <button type="button" @click="close()" class="flex h-9 w-9 items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"><x-admin.icon name="close" class="h-5 w-5" /></button>
            </header>

            <div class="min-h-0 flex-1 grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px]">
                <main class="min-h-0 flex flex-col xl:flex-row bg-[#0f1715]">
                    <div class="min-h-0 flex-1 flex flex-col">
                        <div class="flex shrink-0 flex-wrap items-center gap-2 border-b border-white/10 bg-[#0c1211] px-3 py-2">
                            <button type="button" @click="switchMode('visual')" :class="mode==='visual' ? 'bg-wajhatak-600 text-white' : 'text-white/65 hover:bg-white/10'" class="rounded-lg px-3 py-2 text-xs font-black">مرئي</button>
                            <button type="button" @click="switchMode('code')" :class="mode==='code' ? 'bg-wajhatak-600 text-white' : 'text-white/65 hover:bg-white/10'" class="rounded-lg px-3 py-2 text-xs font-black">HTML / CSS</button>
                            <template x-if="mode==='code'">
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="codePane='html';loadCode()" :class="codePane==='html' ? 'bg-white/10 text-white' : 'text-white/50'" class="rounded-lg px-2.5 py-2 text-[11px] font-black">HTML</button>
                                    <button type="button" @click="codePane='css';loadCode()" :class="codePane==='css' ? 'bg-white/10 text-white' : 'text-white/50'" class="rounded-lg px-2.5 py-2 text-[11px] font-black">CSS</button>
                                </div>
                            </template>
                            <span class="mx-1 hidden h-5 w-px bg-white/10 sm:inline-block"></span>
                            <button type="button" @click="insertVariablePrompt()" class="rounded-lg px-2.5 py-2 text-[11px] font-bold text-white/70 hover:bg-white/10">متغير</button>
                            <button type="button" @click="$refs.asset.click()" class="rounded-lg px-2.5 py-2 text-[11px] font-bold text-white/70 hover:bg-white/10">صورة</button>
                            <input x-ref="asset" type="file" accept="image/png,image/jpeg,image/gif,image/webp" class="hidden" @change="uploadAsset($event)">
                            <button type="button" @click="showIcons=true" class="rounded-lg px-2.5 py-2 text-[11px] font-bold text-white/70 hover:bg-white/10">أيقونات</button>
                            <button type="button" @click="preview()" class="ms-auto rounded-lg bg-white/10 px-2.5 py-2 text-[11px] font-black text-white">معاينة</button>
                        </div>

                        <div x-show="mode==='visual'" class="min-h-0 flex-1 bg-[#dfe8e4]">
                            <div id="emailGjs" class="email-studio-gjs"></div>
                        </div>
                        <div x-show="mode==='code'" class="min-h-0 flex-1 bg-[#0f1715]">
                            <textarea id="emailSourceEditor" class="email-code-editor"></textarea>
                        </div>
                    </div>

                    <section class="min-h-[360px] flex-1 flex flex-col border-t border-white/10 bg-gray-100 dark:bg-gray-950 xl:min-h-0 xl:border-t-0 xl:border-s xl:border-white/10">
                        <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-3 py-2 text-xs font-black dark:border-gray-800">
                            <span>معاينة الرسالة النهائية</span>
                            <div class="flex gap-1"><button type="button" @click="previewDevice='desktop'" :class="previewDevice==='desktop' ? 'bg-gray-900 text-white' : 'bg-gray-200 dark:bg-gray-800'" class="rounded-lg px-2 py-1 text-[10px] font-black">سطح المكتب</button><button type="button" @click="previewDevice='mobile'" :class="previewDevice==='mobile' ? 'bg-gray-900 text-white' : 'bg-gray-200 dark:bg-gray-800'" class="rounded-lg px-2 py-1 text-[10px] font-black">الجوال</button></div>
                        </div>
                        <div class="min-h-0 flex-1 p-3">
                            <iframe id="emailPreviewFrame" title="المعاينة النهائية للبريد" sandbox referrerpolicy="no-referrer" class="h-full min-h-[320px] w-full rounded-2xl bg-white shadow-sm"></iframe>
                        </div>
                    </section>
                </main>

                <aside class="min-h-0 overflow-y-auto border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900 xl:border-t-0 xl:border-s">
                    <div class="space-y-4 p-4">
                        <section class="grid grid-cols-2 gap-3">
                            <div><label class="mb-1.5 block text-[11px] font-black text-gray-500">اسم القالب</label><input x-model="form.name" maxlength="190" class="w-full rounded-xl border bg-gray-50 px-3 py-2.5 text-sm font-bold dark:border-gray-700 dark:bg-gray-800"></div>
                            <div><label class="mb-1.5 block text-[11px] font-black text-gray-500">النوع</label><select x-model="form.template_type" class="w-full rounded-xl border bg-gray-50 px-2 py-2.5 text-xs dark:border-gray-700 dark:bg-gray-800">@foreach($templateTypes as $type=>$label)<option value="{{ $type }}">{{ $label }}</option>@endforeach</select></div>
                        </section>
                        <section><label class="mb-1.5 block text-[11px] font-black text-gray-500">المفتاح البرمجي</label><input x-model="form.key" :disabled="editing && form.is_system" maxlength="100" pattern="[A-Za-z0-9._-]+" class="w-full rounded-xl border bg-gray-50 px-3 py-2.5 font-mono text-xs dark:border-gray-700 dark:bg-gray-800 disabled:opacity-60"><p class="mt-1 text-[10px] text-gray-500">المفتاح هو الرابط بين نوع الرسالة وخدمة الإرسال.</p></section>
                        <section><label class="mb-1.5 block text-[11px] font-black text-gray-500">موضوع الرسالة</label><input x-model="form.subject" maxlength="190" class="w-full rounded-xl border bg-gray-50 px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800"></section>
                        <section><label class="mb-1.5 block text-[11px] font-black text-gray-500">الوصف</label><textarea x-model="form.description" rows="2" maxlength="5000" class="w-full rounded-xl border bg-gray-50 px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800"></textarea></section>
                        <section><label class="mb-1.5 block text-[11px] font-black text-gray-500">Plain Text</label><textarea x-model="form.text_content" rows="5" class="w-full rounded-xl border bg-gray-50 px-3 py-2.5 font-mono text-xs dark:border-gray-700 dark:bg-gray-800"></textarea></section>
                        <section>
                            <div class="mb-2 flex items-center justify-between"><h3 class="text-xs font-black">المتغيرات المعتمدة</h3><span class="text-[10px] text-gray-400" x-text="(form.variables||[]).length"></span></div>
                            <div class="max-h-52 space-y-1 overflow-y-auto">
                                @foreach($variableRegistry as $key=>$definition)
                                    <button type="button" @click="insertVariable(@js($key))" class="w-full rounded-xl border border-gray-200 px-2.5 py-2 text-right hover:border-wajhatak-400 dark:border-gray-700">
                                        <div class="font-mono text-[11px] font-black">&#123;&#123;{{ $key }}&#125;&#125;</div>
                                        <div class="mt-0.5 text-[10px] leading-4 text-gray-500">{{ $definition['description'] }}</div>
                                    </button>
                                @endforeach
                            </div>
                        </section>
                        <section>
                            <h3 class="mb-2 text-xs font-black">بيانات المعاينة</h3>
                            <div class="space-y-2">
                                @foreach($previewValues as $key=>$value)
                                    @if(is_scalar($value))
                                        <label class="block text-[10px] font-bold text-gray-500">{{ $key }}<input value="{{ $value }}" @input="previewValues['{{ $key }}']=$event.target.value;previewDebounced()" class="mt-1 w-full rounded-lg border bg-gray-50 px-2 py-2 text-xs dark:border-gray-700 dark:bg-gray-800"></label>
                                    @endif
                                @endforeach
                            </div>
                        </section>
                    </div>
                </aside>
            </div>

            <footer class="flex shrink-0 flex-wrap items-center gap-2 border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                <span class="me-auto max-w-[50%] truncate text-[11px] text-red-600" x-text="errorMessage"></span>
                <button type="button" @click="openTest()" x-show="editing" :disabled="busy" class="rounded-xl border border-wajhatak-200 px-3 py-2 text-xs font-black text-wajhatak-700 dark:text-wajhatak-300">إرسال تجريبي حقيقي</button>
                <button type="button" @click="publish()" x-show="editing && form.status !== 'published'" :disabled="busy" class="rounded-xl bg-wajhatak-600 px-3 py-2 text-xs font-black text-white disabled:opacity-50">نشر</button>
                <button type="button" @click="close()" class="rounded-xl border px-3 py-2 text-xs font-black">إلغاء</button>
                <button type="button" @click="save()" :disabled="busy || !editorReady" class="rounded-xl bg-gray-900 px-5 py-2 text-xs font-black text-white disabled:opacity-50 dark:bg-gray-100 dark:text-gray-900">حفظ</button>
            </footer>
        </section>
    </div>

    <div x-show="showIcons" x-cloak class="fixed inset-0 z-[130] flex items-center justify-center bg-black/60 p-4" @click.stop>
        <section class="w-full max-w-xl rounded-3xl bg-white p-5 shadow-2xl dark:bg-gray-900" @click.stop>
            <div class="flex items-center"><h3 class="font-black">أيقونات</h3><button type="button" @click="showIcons=false" class="ms-auto rounded-xl border px-3 py-2 text-xs font-black">إغلاق</button></div>
            <div class="mt-4 grid grid-cols-5 gap-2 sm:grid-cols-8">
                @foreach(["✓","✕","★","☆","♥","♡","→","←","☎","✉","⌂","⚡","🔒","🔔","🏠","📍","📅","🕐","💬","⭐","🏷️","🔗","👤","⚙️","🎉","⚠️","ℹ️"] as $icon)
                    <button type="button" @click="insertIcon(@js($icon));showIcons=false" class="flex h-12 items-center justify-center rounded-xl border text-xl hover:border-wajhatak-500 dark:border-gray-700">{{ $icon }}</button>
                @endforeach
            </div>
        </section>
    </div>

    <div x-show="showTest" x-cloak class="fixed inset-0 z-[130] flex items-center justify-center bg-black/60 p-4" @click.stop>
        <section class="w-full max-w-md rounded-3xl bg-white p-5 shadow-2xl dark:bg-gray-900" @click.stop>
            <h3 class="font-black">إرسال رسالة تجريبية حقيقية</h3>
            <p class="mt-1 text-xs leading-5 text-gray-500">سيتم رندرة القالب الحالي من قاعدة البيانات ثم إرساله مباشرة عبر Resend. في وضع Sandbox يفرض Resend استقبال الاختبار على بريد حسابه فقط.</p>
            <input x-model="testRecipient" type="email" maxlength="254" placeholder="you@example.com" class="mt-4 w-full rounded-xl border bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800">
            <div class="mt-4 flex justify-end gap-2"><button type="button" @click="showTest=false" class="rounded-xl border px-3 py-2 text-xs font-black">إلغاء</button><button type="button" @click="sendTest()" :disabled="busy" class="rounded-xl bg-wajhatak-600 px-4 py-2 text-xs font-black text-white disabled:opacity-50">إرسال الآن</button></div>
        </section>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/grapesjs@0.23.6/dist/grapes.min.js"></script>
<script src="https://unpkg.com/grapesjs-preset-newsletter@1.0.2"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/codemirror.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/css/css.min.js"></script>
<script>
function emailTemplateManager() {
    return {
        open:false,editing:false,editorReady:false,busy:false,mode:'visual',codePane:'html',
        showIcons:false,showTest:false,testRecipient:'',previewDevice:'desktop',
        saveState:'جاهز',errorMessage:'',previewTimer:null,autosaveTimer:null,gjs:null,codeEditor:null,
        previewValues:@json($previewValues),form:{},
        blank(){
            return {
                id:null,name:'قالب بريد جديد',key:'',template_type:'custom',subject:'',description:'',
                html_content:'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#f4f7f6;"><tr><td align="center" style="padding:28px 12px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;border-collapse:collapse;background:#ffffff;"><tr><td style="padding:32px;font-family:Arial,sans-serif;text-align:right;direction:rtl;"><h1 style="margin:0 0 14px;color:#075e4a;">مرحبًا \u007B\u007Buser.name\u007D\u007D</h1><p style="margin:0;color:#36443f;">ابدأ بتصميم رسالتك من الكتل الجاهزة.</p></td></tr></table></td></tr></table>',
                css_styles:[],text_content:'',variables:@json(array_keys($variableRegistry)),is_system:false,version:1,status:'draft'
            };
        },
        async init(){
            const qs=new URLSearchParams(location.search);
            if(qs.get('create')==='1') await this.openCreate();
            else if(qs.get('edit')) await this.openEdit(Number(qs.get('edit')));
            window.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'&&this.open){e.preventDefault();this.save();}});
        },
        async openCreate(){
            this.editing=false;this.form=this.blank();this.previewValues=@json($previewValues);this.errorMessage='';this.saveState='مسودة جديدة';this.open=true;
            await this.$nextTick(); await this.ensureEditor(); this.loadVisual(); this.refreshLayout(); this.previewDebounced();
        },
        async openEdit(id){
            this.busy=true;this.open=true;this.errorMessage='';this.saveState='جارٍ تحميل القالب…';
            await this.$nextTick();
            try{
                const r=await fetch(@json(url('/admin/email-templates'))+'/'+id+'/editor-data',{credentials:'same-origin',headers:{Accept:'application/json'}});
                const d=await r.json();if(!r.ok)throw new Error(d.message||'تعذر تحميل القالب');
                this.form=d.data;this.previewValues=@json($previewValues);this.editing=true;await this.ensureEditor();this.loadVisual();this.refreshLayout();this.saveState='تم تحميل القالب';this.previewDebounced();
            }catch(e){this.errorMessage=e.message||'تعذر تحميل القالب';this.saveState='فشل التحميل';}finally{this.busy=false;}
        },
        async ensureEditor(){
            if(!window.grapesjs)throw new Error('تعذر تحميل GrapesJS. تحقق من اتصال المتصفح بشبكة CDN.');
            this.editorReady=true;
            if(!this.gjs){
                this.gjs=grapesjs.init({
                    container:'#emailGjs',height:'100%',width:'auto',storageManager:false,
                    fromElement:false,clearOnRender:true,
                    plugins:['grapesjs-preset-newsletter'],
                    pluginsOpts:{'grapesjs-preset-newsletter':{inlineCss:true,showBlocksOnLoad:true,updateStyleManager:true,useCustomTheme:true}},
                    assetManager:{upload:@json(route('admin.email-templates.assets')),uploadName:'file',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},params:{'_token':document.querySelector('meta[name="csrf-token"]')?.content||''},credentials:'same-origin',autoAdd:true},
                    selectorManager:{componentFirst:true},
                });
                this.gjs.on('update',()=>{if(this.mode==='visual'){this.syncVisual();this.saveState='تغييرات غير محفوظة';this.previewDebounced();this.queueAutosave();}});
            }
        },
        loadVisual(){
            if(!this.gjs)return;
            this.gjs.setComponents(this.form.html_content||this.blank().html_content);
            this.gjs.setStyle(Array.isArray(this.form.css_styles)?this.form.css_styles.join('\n'):String(this.form.css_styles||''));
            this.mode='visual';setTimeout(()=>this.refreshLayout(),60);
        },
        refreshLayout(){if(this.gjs){this.gjs.refresh();this.gjs.Canvas.getBody()?.scrollTo?.(0,0);}},
        syncVisual(){if(!this.gjs)return;this.form.html_content=this.gjs.getHtml();this.form.css_styles=[this.gjs.getCss()||''];},
        async switchMode(next){
            if(next===this.mode)return;
            if(this.mode==='visual')this.syncVisual();
            this.mode=next;
            if(next==='code'){await this.ensureCodeEditor();this.loadCode();}
            else{this.syncCodeToForm();this.loadVisual();}
            this.refreshLayout();this.previewDebounced();
        },
        async ensureCodeEditor(){
            if(this.codeEditor||!window.CodeMirror)return;
            this.codeEditor=CodeMirror.fromTextArea(document.getElementById('emailSourceEditor'),{
                mode:'htmlmixed',theme:'default',lineNumbers:true,lineWrapping:true,tabSize:2,indentUnit:2,
                autoCloseTags:true,matchBrackets:true,viewportMargin:80,extraKeys:{'Ctrl-S':()=>this.save(),'Cmd-S':()=>this.save()}
            });
            this.codeEditor.setSize('100%','100%');
            this.codeEditor.on('change',()=>{this.syncCodeToForm();this.saveState='تغييرات غير محفوظة';this.previewDebounced();this.queueAutosave();});
        },
        syncCodeToForm(){
            if(!this.codeEditor)return;
            if(this.codePane==='html')this.form.html_content=this.codeEditor.getValue();
            else this.form.css_styles=[this.codeEditor.getValue()];
        },
        loadCode(){
            if(!this.codeEditor)return;
            this.codeEditor.setOption('mode',this.codePane==='html'?'htmlmixed':'css');
            this.codeEditor.setValue(this.codePane==='html'?String(this.form.html_content||''):Array.isArray(this.form.css_styles)?this.form.css_styles.join('\n'):String(this.form.css_styles||''));
            setTimeout(()=>this.codeEditor.refresh(),30);
        },
        currentHtml(){if(this.mode==='visual'){this.syncVisual();return String(this.form.html_content||'');}this.syncCodeToForm();return String(this.form.html_content||'');},
        currentCss(){if(this.mode==='visual'){this.syncVisual();return Array.isArray(this.form.css_styles)?this.form.css_styles:[];}this.syncCodeToForm();return Array.isArray(this.form.css_styles)?this.form.css_styles:[];},
        formPayload(){
            return {
                name:this.form.name, key:this.form.key, template_type:this.form.template_type, description:this.form.description,
                subject:this.form.subject, html_content:this.currentHtml(), text_content:this.form.text_content,
                css_styles:this.currentCss(), variables:this.form.variables||[], preview_variables:this.previewValues,
            };
        },
        previewDebounced(){clearTimeout(this.previewTimer);this.previewTimer=setTimeout(()=>this.preview(),350);},
        async preview(){
            if(!this.open||!this.editorReady)return;
            try{
                const payload=this.formPayload();
                const r=await fetch(@json(route('admin.email-templates.preview-draft')),{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:JSON.stringify(payload)});
                const d=await r.json();if(!r.ok)throw new Error(d.message||'تعذر إنشاء المعاينة');
                const frame=document.getElementById('emailPreviewFrame');if(frame){frame.srcdoc=d.html||'<p style="padding:24px">لا يوجد محتوى للمعاينة.</p>';frame.style.maxWidth=this.previewDevice==='mobile'?'390px':'100%';frame.parentElement?.classList.toggle('flex',this.previewDevice==='mobile');}
                this.errorMessage=(d.unknown_variables||[]).length?'متغيرات غير معروفة: '+d.unknown_variables.join(', '):'';
            }catch(e){if(!String(e?.name).includes('Abort'))this.errorMessage=e.message||'تعذر إنشاء المعاينة';}
        },
        queueAutosave(){
            if(!this.editing||!this.form.id)return;
            clearTimeout(this.autosaveTimer);this.autosaveTimer=setTimeout(()=>this.autosave(),1800);
        },
        async autosave(){
            if(this.busy||!this.editing||!this.form.id)return;
            const before=this.saveState;this.saveState='حفظ تلقائي…';
            try{
                const r=await fetch(@json(url('/admin/email-templates'))+'/'+this.form.id+'/autosave',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:JSON.stringify(this.formPayload())});
                const d=await r.json();if(!r.ok)throw new Error(d.message||'تعذر الحفظ التلقائي');
                this.form.version=d.version||this.form.version;this.form.status=d.status||this.form.status;this.saveState='حفظ تلقائي ✓';
            }catch(e){this.saveState=before;this.errorMessage=e.message||'فشل الحفظ التلقائي';}
        },
        async save(){
            if(this.busy||!this.editorReady)return;
            this.busy=true;clearTimeout(this.autosaveTimer);this.saveState='جارٍ الحفظ…';this.errorMessage='';
            try{
                const payload=this.formPayload();
                if(!payload.name||!payload.key||!payload.subject||!payload.html_content){throw new Error('أكمل اسم القالب والمفتاح والموضوع ومحتوى HTML.');}
                const base=@json(url('/admin/email-templates'));
                const endpoint=this.editing?base+'/'+this.form.id:base;
                const r=await fetch(endpoint,{method:this.editing?'PATCH':'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:JSON.stringify(payload)});
                const d=await r.json();if(!r.ok)throw new Error(Object.values(d.errors||{}).flat().join(' ')||d.message||'تعذر حفظ القالب');
                this.editing=true;this.form.id=d.data?.id||this.form.id;this.form.version=d.data?.version||this.form.version;this.form.status=d.data?.status||'draft';this.form.key=d.data?.key||this.form.key;this.saveState='تم الحفظ ✓';
                history.replaceState({},'',@json(url('/admin/email-templates'))+'?edit='+this.form.id);
            }catch(e){this.errorMessage=e.message||'فشل الحفظ';this.saveState='فشل الحفظ';}finally{this.busy=false;}
        },
        async publish(){
            if(!this.editing)return;
            if(this.saveState.includes('غير محفوظ'))await this.save();
            this.busy=true;this.saveState='جارٍ النشر…';
            try{
                const r=await fetch(@json(url('/admin/email-templates'))+'/'+this.form.id+'/publish',{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''}});
                const d=await r.json();if(!r.ok)throw new Error(d.message||'تعذر نشر القالب');
                this.form.status='published';this.form.version=d.version||this.form.version;this.saveState='تم النشر ✓';
            }catch(e){this.errorMessage=e.message||'فشل النشر';this.saveState='فشل النشر';}finally{this.busy=false;}
        },
        insertVariable(name){
            const token='{{'+name+'}}';
            if(this.mode==='visual'){const c=this.gjs?.getSelected();if(c?.is('textable')){c.view?.$el?.focus?.();c.set('content',(c.get('content')||'')+token);}else if(this.gjs){this.gjs.addComponents('<p style="margin:0 0 12px;font-family:Arial,sans-serif;">'+token+'</p>');}}
            else if(this.codeEditor){this.codeEditor.replaceSelection(token);this.codeEditor.focus();}
            this.previewDebounced();
        },
        insertVariablePrompt(){const keys=@json(array_keys($variableRegistry));const key=prompt('اكتب اسم المتغير مثل user.name أو property.price');if(key&&keys.includes(key))this.insertVariable(key);},
        insertIcon(icon){const raw='<span style="display:inline-block;font-size:24px;line-height:1;">'+icon+'</span>';if(this.mode==='visual')this.gjs?.addComponents(raw);else this.codeEditor?.replaceSelection(raw);this.previewDebounced();},
        async uploadAsset(event){
            const file=event.target.files?.[0];event.target.value='';if(!file)return;
            const fd=new FormData();fd.append('file',file);
            try{
                const r=await fetch(@json(route('admin.email-templates.assets')),{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:fd});
                const d=await r.json();if(!r.ok)throw new Error(d.message||'تعذر رفع الصورة');
                const src=d.data?.[0]?.src;if(src&&this.mode==='visual')this.gjs?.addComponents({type:'image',src,attributes:{alt:file.name},style:{width:'100%','max-width':'100%','height':'auto'}});
                else if(src)this.codeEditor?.replaceSelection('<img src="'+src+'" alt="'+file.name.replace(/"/g,'&quot;')+'" style="display:block;width:100%;max-width:100%;height:auto;">');
                this.previewDebounced();
            }catch(e){this.errorMessage=e.message||'تعذر رفع الصورة';}
        },
        openTest(){this.testRecipient='';this.showTest=true;},
        async sendTest(){
            if(!this.form.id){await this.save();if(!this.form.id)return;}
            this.busy=true;this.errorMessage='';
            try{
                const r=await fetch(@json(url('/admin/email-templates'))+'/'+this.form.id+'/test-email',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:JSON.stringify({recipient:this.testRecipient,...this.formPayload()})});
                const d=await r.json();if(!r.ok)throw new Error(d.message||'تعذر إرسال الرسالة التجريبية');
                this.showTest=false;this.saveState='تم الإرسال عبر Resend ✓';
            }catch(e){this.errorMessage=e.message||'فشل الإرسال';}finally{this.busy=false;}
        },
        close(){
            if(this.busy)return;clearTimeout(this.previewTimer);clearTimeout(this.autosaveTimer);this.open=false;this.showIcons=false;this.showTest=false;this.editorReady=false;history.replaceState({},'',location.pathname);
        }
    };
}
</script>
@endpush
</x-admin.layouts.admin>
