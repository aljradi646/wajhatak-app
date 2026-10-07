@php
    $statusLabels = ['draft' => 'مسودة', 'published' => 'منشور', 'archived' => 'مؤرشف'];
@endphp
<x-admin.layouts.admin heading="قوالب البريد الإلكتروني" title="قوالب البريد">
<div class="max-w-7xl mx-auto space-y-5">
    <div class="flex flex-wrap items-center gap-3">
        <div class="me-auto"><h2 class="text-xl font-black">Email Template Studio</h2><p class="mt-1 text-sm text-gray-500">قوالب مرئية وكودية مع إصدارات مستقلة ونشر آمن.</p></div>
        <a href="{{ route('admin.email-templates.create') }}" class="rounded-xl bg-wajhatak-600 px-4 py-2.5 text-sm font-black text-white">إنشاء قالب</a>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
    @foreach($templates as $template)
        @php $status = $template->status ?: ($template->is_active ? 'published' : 'archived'); @endphp
        <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-start gap-3">
                <div class="me-auto min-w-0"><h3 class="truncate font-black">{{ $template->name }}</h3><p class="mt-1 font-mono text-xs text-gray-500">{{ $template->key }}</p></div>
                <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $status === 'published' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : ($status === 'archived' ? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300') }}">{{ $statusLabels[$status] ?? $status }}</span>
            </div>
            @if($template->description)<p class="mt-4 line-clamp-3 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $template->description }}</p>@endif
            <dl class="mt-4 grid grid-cols-2 gap-2 text-xs text-gray-500"><div><dt>الإصدار</dt><dd class="font-black text-gray-800 dark:text-gray-200">v{{ $template->version }}</dd></div><div><dt>المنشور</dt><dd class="font-black text-gray-800 dark:text-gray-200">{{ $template->published_version ? 'v'.$template->published_version : '—' }}</dd></div></dl>
            <div class="mt-4 flex gap-2"><a href="{{ route('admin.email-templates.edit',$template) }}" class="flex-1 rounded-xl bg-gray-900 px-3 py-2 text-center text-sm font-black text-white dark:bg-gray-100 dark:text-gray-900">فتح الاستوديو</a><a href="{{ route('admin.email-templates.history',$template) }}" class="rounded-xl border px-3 py-2 text-sm font-black">السجل</a></div>
            @if(!$template->is_system)
            <div class="mt-2 flex gap-2"><form method="POST" action="{{ route('admin.email-templates.duplicate',$template) }}" class="flex-1">@csrf<button class="w-full rounded-xl border px-3 py-2 text-sm font-black">نسخ</button></form><form method="POST" action="{{ route('admin.email-templates.destroy',$template) }}" class="flex-1" onsubmit="return confirm('حذف القالب نهائيًا؟')">@csrf @method('DELETE')<button class="w-full rounded-xl border border-red-200 px-3 py-2 text-sm font-black text-red-700">حذف</button></form></div>
            @endif
        </article>
    @endforeach
    </div>
    {{ $templates->links() }}
</div>
</x-admin.layouts.admin>
