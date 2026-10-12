<x-guest-layout>
    <div class="space-y-6" dir="rtl">
        <div class="text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/><path d="M12 14v3"/></svg>
            </div>
            <h1 class="text-2xl font-black text-gray-900 dark:text-gray-100">التحقق بخطوتين</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة لإكمال تسجيل دخول المشرف.</p>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-900/30 dark:text-red-300">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.2fa.verify') }}" class="space-y-4">
            @csrf
            <label for="code" class="block text-sm font-bold text-gray-700 dark:text-gray-200">رمز التحقق</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" minlength="6" autocomplete="one-time-code" required autofocus dir="ltr" class="w-full rounded-2xl border border-gray-300 bg-gray-50 px-4 py-4 text-center text-2xl font-bold tracking-[0.5em] text-gray-900 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white" placeholder="000000">
            <button type="submit" class="w-full rounded-2xl bg-emerald-700 px-5 py-3.5 font-extrabold text-white transition hover:bg-emerald-800">تحقق وتابع</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="text-sm font-semibold text-gray-500 hover:text-red-600">إلغاء تسجيل الدخول</button>
        </form>
    </div>
</x-guest-layout>
