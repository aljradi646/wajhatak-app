<x-guest-layout>
    <div x-data="{ showPass: false, loading: false }" class="space-y-6">
        {{-- Header --}}
        <div class="text-center">
            <h2 class="text-2xl font-black text-gray-900 dark:text-gray-100">مرحبًا بعودتك</h2>
            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">سجّل دخولك للوصول إلى لوحة تحكم وجهتك.</p>
        </div>

        {{-- Session status / error --}}
        @if(session('status'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:border-green-900 dark:bg-green-900/30 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif
        @if(session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900 dark:bg-red-900/30 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('login') }}" class="space-y-5" @submit="loading = true">
            @csrf

            {{-- Email --}}
            <div>
                <label for="email" class="mb-2 block text-sm font-bold text-gray-700 dark:text-gray-200">البريد الإلكتروني</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </span>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="example@email.com"
                        dir="ltr"
                        class="w-full rounded-2xl border border-gray-300 bg-gray-50 py-3.5 pr-12 pl-4 text-base text-gray-900 placeholder:text-gray-400 transition focus:bg-white focus:border-wajhatak-500 focus:ring-2 focus:ring-wajhatak-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:bg-gray-700"
                    >
                </div>
                @error('email')
                    <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="mb-2 block text-sm font-bold text-gray-700 dark:text-gray-200">كلمة المرور</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </span>
                    <input
                        id="password"
                        :type="showPass ? 'text' : 'password'"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        dir="ltr"
                        class="w-full rounded-2xl border border-gray-300 bg-gray-50 py-3.5 pr-12 pl-12 text-base text-gray-900 placeholder:text-gray-400 transition focus:bg-white focus:border-wajhatak-500 focus:ring-2 focus:ring-wajhatak-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:bg-gray-700"
                    >
                    <button
                        type="button"
                        @click="showPass = !showPass"
                        class="absolute inset-y-0 left-0 flex items-center pr-3 pl-4 text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200"
                        :aria-label="showPass ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
                        title="إظهار / إخفاء كلمة المرور"
                    >
                        <svg x-show="!showPass" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="showPass" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember + Forgot --}}
            <div class="flex items-center justify-between">
                <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2">
                    <input
                        id="remember_me"
                        type="checkbox"
                        name="remember"
                        class="h-4 w-4 rounded border-gray-300 text-wajhatak-600 focus:ring-wajhatak-300 dark:border-gray-600 dark:bg-gray-800"
                    >
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">تذكرني</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm font-bold hover:underline" style="color: #0E8A6D;">
                        نسيت كلمة المرور؟
                    </a>
                @endif
            </div>

            {{-- Submit --}}
            <button
                type="submit"
                :disabled="loading"
                class="group flex w-full items-center justify-center gap-2 rounded-2xl px-6 py-4 text-base font-extrabold text-white transition focus:outline-none focus:ring-2 focus:ring-wajhatak-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70"
                style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E); box-shadow: 0 8px 22px rgba(14, 138, 109, 0.35);"
            >
                <template x-if="!loading">
                    <svg class="h-5 w-5 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                </template>
                <template x-if="loading">
                    <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3a9 9 0 109 9h-3a6 6 0 11-6-6V3z"/></svg>
                </template>
                <span x-text="loading ? 'جارٍ تسجيل الدخول…' : 'تسجيل الدخول'"></span>
            </button>
        </form>

        {{-- Register CTA --}}
        <div class="pt-1">
            <div class="mb-4 flex items-center gap-3">
                <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">أو</span>
                <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
            </div>

            <a
                href="{{ route('register') }}"
                class="flex w-full items-center justify-center gap-2 rounded-2xl border-2 px-6 py-3.5 text-sm font-extrabold transition hover:shadow-md"
                style="border-color: #0E8A6D; color: #0E8A6D;"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                إنشاء حساب جديد
            </a>
        </div>
    </div>
</x-guest-layout>
