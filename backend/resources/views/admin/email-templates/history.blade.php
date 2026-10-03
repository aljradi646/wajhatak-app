<x-admin.layouts.admin heading="سجل إصدارات قالب البريد" title="سجل الإصدارات">
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-black">{{ $template->name }}</h1>
                <p class="text-sm text-gray-500">كل استعادة تنشئ إصدارًا جديدًا ولا تحذف التاريخ.</p>
            </div>
            <a href="{{ route('admin.email-templates.edit',$template) }}" class="rounded-xl border px-4 py-2 text-sm font-bold">العودة للتحرير</a>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr><th class="px-4 py-3 text-right">الإصدار</th><th class="px-4 py-3 text-right">الموضوع</th><th class="px-4 py-3 text-right">المنشئ</th><th class="px-4 py-3 text-right">التاريخ</th><th class="px-4 py-3 text-right">الإجراء</th></tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                @forelse($versions as $version)
                    <tr>
                        <td class="px-4 py-3 font-black">v{{ $version->version }}</td>
                        <td class="px-4 py-3">{{ $version->subject }}</td>
                        <td class="px-4 py-3">{{ $version->creator?->name ?? 'النظام' }}</td>
                        <td class="px-4 py-3">{{ $version->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.email-templates.restore',[$template,$version]) }}" onsubmit="return confirm('استعادة هذا الإصدار كنسخة جديدة؟')">
                                @csrf
                                <button class="rounded-lg border px-3 py-1.5 font-bold hover:bg-gray-50 dark:hover:bg-gray-800">استعادة</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">لا توجد إصدارات محفوظة بعد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin.layouts.admin>