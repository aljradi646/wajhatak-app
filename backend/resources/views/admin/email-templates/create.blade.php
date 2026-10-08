@php
$variableKeys = array_keys($variables);
@endphp
<x-admin.layouts.admin heading="إنشاء قالب بريد جديد" title="استوديو إنشاء قالب">
<div x-data="newEmailTemplateStudio()" x-init="init()" class="space-y-4">
<form id="newTemplateForm" method="POST" action="{{ route('admin.email-templates.store') }}" @submit="prepareSubmit()">
@csrf
<input type="hidden" name="html_content" id="newHtmlField">
<input type="hidden" name="css_styles" id="newCssField">
<input type="hidden" name="variables" id="newVariablesField">
<input type="hidden" name="text_content" id="newTextField">
<section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
<div class="flex flex-wrap items-center gap-3">
<div class="min-w-[280px] flex-1">
<input name="name" required maxlength="190" placeholder="اسم القالب" class="w-full rounded-xl border bg-gray-50 px-3 py-2.5 text-lg font-black dark:bg-gray-800">
<input name="key" required maxlength="100" pattern="[A-Za-z0-9._-]+" placeholder="template_key" class="mt-2 w-full rounded-xl border bg-gray-50 px-3 py-2 font-mono text-sm dark:bg-gray-800">
</div>
<div class="min-w-[280px] flex-1"><input name="subject" required maxlength="190" placeholder="موضوع الرسالة" class="w-full rounded-xl border bg-gray-50 px-3 py-2.5 font-black dark:bg-gray-800"><textarea name="description" rows="2" class="mt-2 w-full rounded-xl border bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800" placeholder="وصف القالب واستخدامه"></textarea></div>
</div>
</section>
<div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.55fr)_minmax(360px,.75fr)]">
<section class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
<div class="flex flex-wrap items-center gap-2 border-b border-gray-200 p-3 dark:border-gray-700">
<button type="button" @click="switchMode('visual')" :class="mode==='visual'?'bg-wajhatak-600 text-white':'bg-gray-100 dark:bg-gray-800'" class="rounded-lg px-3 py-2 text-sm font-black">المحرر المرئي</button>
<button type="button" @click="switchMode('code')" :class="mode==='code'?'bg-wajhatak-600 text-white':'bg-gray-100 dark:bg-gray-800'" class="rounded-lg px-3 py-2 text-sm font-black">HTML / CSS احترافي</button>
<button type="button" @click="$refs.file.click()" class="rounded-lg border px-3 py-2 text-sm font-black">صورة من الجهاز</button>
<input x-ref="file" type="file" accept="image/png,image/jpeg,image/gif,image/webp" class="hidden" @change="uploadAsset($event)">
<button type="button" @click="showIcons=true" class="rounded-lg border px-3 py-2 text-sm font-black">الأيقونات</button>
<button type="button" @click="insertVariablePrompt()" class="rounded-lg border px-3 py-2 text-sm font-black">متغير</button>
</div>
<div x-show="mode==='visual'" x-cloak><div id="newGjs" class="min-h-[760px]"></div></div>
<div x-show="mode==='code'" x-cloak class="grid grid-rows-[540px_220px]"><div id="newMonacoHtml" class="border-b border-gray-200 dark:border-gray-700"></div><div id="newMonacoCss"></div></div>
</section>
<aside class="space-y-4">
<section class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
<div class="mb-3 flex items-center justify-between"><h2 class="font-black">معاينة حقيقية</h2><div class="flex gap-1"><button type="button" @click="device='desktop';refreshPreview()" class="rounded-lg px-2.5 py-1.5 text-xs font-black" :class="device==='desktop'?'bg-gray-900 text-white':'bg-gray-100 dark:bg-gray-800'">سطح المكتب</button><button type="button" @click="device='mobile';refreshPreview()" class="rounded-lg px-2.5 py-1.5 text-xs font-black" :class="device==='mobile'?'bg-gray-900 text-white':'bg-gray-100 dark:bg-gray-800'">الجوال</button></div></div>
<div class="overflow-hidden rounded-xl border bg-gray-100 p-2 dark:bg-gray-950"><iframe id="newPreviewFrame" title="معاينة البريد" sandbox referrerpolicy="no-referrer" class="mx-auto h-[620px] w-full rounded-lg bg-white"></iframe></div>
</section>
<section class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900"><h2 class="mb-3 font-black">المتغيرات</h2><div class="max-h-64 space-y-1 overflow-y-auto">@foreach($variables as $key=>$definition)<button type="button" @click="insertVariable(@js($key))" class="w-full rounded-lg border px-2.5 py-2 text-right"><div class="font-mono text-xs font-black">&#123;&#123;{{ $key }}&#125;&#125;</div><div class="text-[11px] text-gray-500">{{ $definition['description'] }}</div></button>@endforeach</div></section>
<section class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900"><h2 class="mb-2 font-black">بيانات المعاينة</h2><div class="grid gap-2">@foreach($previewValues as $key=>$value)<label class="text-xs font-bold">{{ $key }}<input type="text" value="{{ is_scalar($value)?$value:'' }}" @input="previewValues['{{ $key }}']=$event.target.value;refreshPreviewDebounced()" class="mt-1 w-full rounded-lg border bg-gray-50 px-2.5 py-2 text-xs dark:bg-gray-800"></label>@endforeach</div></section>
<section class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900"><h2 class="mb-2 font-black">النص البديل</h2><textarea x-model="textContent" rows="6" class="w-full rounded-xl border bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800"></textarea></section>
</aside></div>
<div class="flex items-center justify-end gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900"><a href="{{ route('admin.email-templates.index') }}" class="rounded-lg border px-4 py-2.5 text-sm font-black">إلغاء</a><button type="submit" :disabled="busy" class="rounded-lg bg-wajhatak-600 px-5 py-2.5 text-sm font-black text-white disabled:opacity-50">إنشاء القالب وفتح الاستوديو</button></div>
</form>
<div x-show="showIcons" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4"><div class="w-full max-w-2xl rounded-2xl bg-white p-5 shadow-2xl dark:bg-gray-900"><div class="flex items-center justify-between"><h2 class="text-lg font-black">مكتبة أيقونات جاهزة</h2><button type="button" @click="showIcons=false" class="rounded-lg border px-3 py-2">إغلاق</button></div><div class="mt-4 grid grid-cols-5 gap-2 sm:grid-cols-8">@foreach(["✓","✕","★","☆","♥","♡","→","←","☎","✉","⌂","⚡","🔒","🔔","🏠","📍","📅","🕐","💬","⭐","🏷️","🔗","👤","⚙️","🎉","⚠️","ℹ️"] as $icon)<button type="button" @click="insertIcon(@js($icon));showIcons=false" class="flex h-14 items-center justify-center rounded-xl border text-2xl hover:border-wajhatak-500">{{ $icon }}</button>@endforeach</div></div></div>
</div>
@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/grapesjs@0.23.6/dist/css/grapes.min.css">
<script src="https://unpkg.com/grapesjs@0.23.6"></script><script src="https://unpkg.com/grapesjs-preset-newsletter@1.0.2"></script><script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.55.1/min/vs/loader.js"></script>
<script>
function newEmailTemplateStudio(){
return {
mode:'visual',device:'desktop',busy:false,showIcons:false,textContent:'',previewValues:@json($previewValues),visualEditor:null,htmlEditor:null,cssEditor:null,previewTimer:null,previewController:null,
async init(){this.mountVisual();await this.loadMonaco();this.refreshPreview();},
mountVisual(){this.visualEditor=grapesjs.init({container:'#newGjs',height:'760px',storageManager:false,assetManager:{upload:@json(route('admin.email-templates.assets')),uploadName:'file',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},autoAdd:true},plugins:['gjs-preset-newsletter'],pluginsOpts:{'gjs-preset-newsletter':{inlineCss:true,showBlocksOnLoad:true,updateStyleManager:true}},components:'<table role="presentation" width="100%" style="background:#f5f7f8"><tr><td style="padding:32px"><table role="presentation" width="100%" style="max-width:600px;margin:auto;background:#ffffff"><tr><td style="padding:32px;text-align:center"><h1 style="margin:0 0 12px">عنوان الرسالة</h1><p style="margin:0">اكتب محتوى البريد هنا.</p></td></tr></table></td></tr></table>'});this.visualEditor.on('component:update component:styleUpdate',()=>this.refreshPreviewDebounced());},
async loadMonaco(){await new Promise((resolve)=>{const start=()=>{require.config({paths:{vs:'https://cdn.jsdelivr.net/npm/monaco-editor@0.55.1/min/vs'}});require(['vs/editor/editor.main'],()=>{const theme=document.documentElement.classList.contains('dark')?'vs-dark':'vs';this.htmlEditor=monaco.editor.create(document.getElementById('newMonacoHtml'),{value:this.visualHtml(),language:'html',theme,automaticLayout:true,minimap:{enabled:true},wordWrap:'on',tabSize:2,formatOnPaste:true,formatOnType:true});this.cssEditor=monaco.editor.create(document.getElementById('newMonacoCss'),{value:'',language:'css',theme,automaticLayout:true,minimap:{enabled:false},wordWrap:'on',tabSize:2});this.htmlEditor.onDidChangeModelContent(()=>this.refreshPreviewDebounced());this.cssEditor.onDidChangeModelContent(()=>this.refreshPreviewDebounced());resolve();});};if(window.require?.config)start();else{const t=setInterval(()=>{if(window.require?.config){clearInterval(t);start();}},50);}});},
visualHtml(){return this.visualEditor?.getHtml()||'';},visualCss(){return this.visualEditor?.getCss()||'';},currentHtml(){return this.mode==='code'&&this.htmlEditor?this.htmlEditor.getValue():this.visualHtml();},currentCss(){return this.mode==='code'&&this.cssEditor?this.cssEditor.getValue():this.visualCss();},
switchMode(next){if(next===this.mode)return;if(next==='code'){this.htmlEditor?.setValue(this.visualHtml());this.cssEditor?.setValue(this.visualCss());}else{this.visualEditor?.setComponents(this.htmlEditor?.getValue()||'');this.visualEditor?.setStyle(this.cssEditor?.getValue()||'');}this.mode=next;this.refreshPreview();},
formData(){const f=document.getElementById('newTemplateForm');return{name:f.elements.name.value,description:f.elements.description.value,subject:f.elements.subject.value,html_content:this.currentHtml(),text_content:this.textContent,css_styles:[this.currentCss()],variables:@json($variableKeys),preview_variables:this.previewValues};},
async refreshPreview(){this.previewController?.abort();this.previewController=new AbortController();try{const r=await fetch(@json(route('admin.email-templates.preview-draft')),{method:'POST',signal:this.previewController.signal,headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:JSON.stringify(this.formData())});const d=await r.json();if(!r.ok)return;const f=document.getElementById('newPreviewFrame');f.style.maxWidth=this.device==='mobile'?'390px':'100%';f.srcdoc=d.html||'';}catch(e){if(e.name!=='AbortError')console.error(e);}},
refreshPreviewDebounced(){clearTimeout(this.previewTimer);this.previewTimer=setTimeout(()=>this.refreshPreview(),500);},
async uploadAsset(event){const file=event.target.files?.[0];event.target.value='';if(!file)return;const fd=new FormData();fd.append('file',file);try{const r=await fetch(@json(route('admin.email-templates.assets')),{method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:fd});const d=await r.json();if(!r.ok)throw new Error(d.message||'تعذر رفع الصورة');const src=d.data?.[0]?.src;if(src)this.visualEditor?.addComponents({type:'image',src,alt:file.name,style:{'max-width':'100%','height':'auto','display':'block','margin':'0 auto'}});this.refreshPreview();}catch(e){alert(e.message||'تعذر رفع الصورة.');}},
insertVariable(name){const token='{{{{ '+name+' }}}}';if(this.mode==='code'&&this.htmlEditor){const s=this.htmlEditor.getSelection();this.htmlEditor.executeEdits('insert-variable',[{range:s,text:token,forceMoveMarkers:true}]);}else{const selected=this.visualEditor?.getSelected();if(selected)selected.append('<p>'+token+'</p>');else this.visualEditor?.addComponents('<p>'+token+'</p>');}this.refreshPreview();},
insertVariablePrompt(){const key=prompt('اسم المتغير، مثل user.name أو property.price');if(key&&@json($variableKeys).includes(key))this.insertVariable(key);},
insertIcon(icon){const html='<span style="font-size:24px;line-height:1;display:inline-block">'+icon+'</span>';if(this.mode==='code'&&this.htmlEditor){const s=this.htmlEditor.getSelection();this.htmlEditor.executeEdits('insert-icon',[{range:s,text:html,forceMoveMarkers:true}]);}else{const selected=this.visualEditor?.getSelected();if(selected)selected.append(html);else this.visualEditor?.addComponents(html);}this.refreshPreview();},
prepareSubmit(){document.getElementById('newHtmlField').value=this.currentHtml();document.getElementById('newCssField').value=JSON.stringify([this.currentCss()]);document.getElementById('newVariablesField').value=JSON.stringify(@json($variableKeys));document.getElementById('newTextField').value=this.textContent;this.busy=true;}
};
}
</script>
@endpush
</x-admin.layouts.admin>
