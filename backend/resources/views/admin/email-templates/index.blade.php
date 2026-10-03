<x-admin.layouts.admin heading="قوالب البريد الإلكتروني" title="قوالب البريد">
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">إدارة قوالب البريد</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">قم بإنشاء وتخصيص قوالب البريد الإلكتروني بـ HTML/CSS</p>
            </div>
            <a href="{{ route('admin.email-templates.create') }}" class="btn-brand">إنشاء قالب جديد</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($templates as $template)
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 hover:shadow-lg transition">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-gray-100">{{ $template->name }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $template->key }}</p>
                        </div>
                        @if($template->is_system)
                            <span class="px-2 py-1 text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded">نظامي</span>
                        @else
                            @if($template->is_active)
                                <span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 rounded">نشط</span>
                            @else
                                <span class="px-2 py-1 text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded">معطل</span>
                            @endif
                        @endif
                    </div>
                    
                    @if($template->description)
                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">{{ $template->description }}</p>
                    @endif
                    
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        الإصدار: {{ $template->version }}
                    </div>
                    
                    <div class="flex gap-2">
                        <a href="{{ route('admin.email-templates.edit', $template) }}" class="flex-1 text-center px-3 py-2 text-sm font-bold bg-wajhatak-600 text-white rounded-lg hover:bg-wajhatak-700 transition">
                            تعديل
                        </a>
                        @if(!$template->is_system)
                            <form method="POST" action="{{ route('admin.email-templates.duplicate', $template) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full px-3 py-2 text-sm font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                نسخ
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.email-templates.destroy', $template) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا القالب؟');" class="flex-1">
                                @method('DELETE')
                                @csrf
                                <button type="submit" class="w-full px-3 py-2 text-sm font-bold bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 transition">
                                حذف
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if($templates->isEmpty())
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📧</div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">لا توجد قوالب بريد</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">ابدأ بإنشاء قالب بريد جديد</p>
                <a href="{{ route('admin.email-templates.create') }}" class="btn-brand">إنشاء قالب جديد</a>
            </div>
        @endif
    </div>
</x-admin.layouts.admin>
