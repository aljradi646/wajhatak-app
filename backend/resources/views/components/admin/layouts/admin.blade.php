@props(['heading' => 'لوحة التحكم', 'title' => 'لوحة تحكم وجهتك'])

<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }} — {{ config('app.name', 'وجهتك') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('storage/branding/logo-small.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .fi-body { font-family: 'Cairo', ui-sans-serif, system-ui, sans-serif; }
            [x-cloak] { display: none !important; }

            /* براند مارك بتدرج زمردي */
            .brand-mark {
                background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);
                box-shadow: 0 4px 14px rgba(14, 138, 109, 0.3);
            }

            /* أزرار أساسية بتدرج زمردي */
            .btn-brand {
                background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);
                box-shadow: 0 4px 14px rgba(14, 138, 109, 0.25);
                color: #fff;
            }
            .btn-brand:hover {
                box-shadow: 0 6px 20px rgba(14, 138, 109, 0.4);
                transform: translateY(-1px);
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-100" style="font-family:'Cairo',ui-sans-serif,system-ui,sans-serif;">
        <div
            x-data="{ sidebarOpen: false, collapsed: (window.matchMedia('(min-width: 1024px)').matches && localStorage.getItem('lux_sidebar') === '1') }"
            x-init="$watch('collapsed', value => localStorage.setItem('lux_sidebar', value ? '1' : '0'))"
            class="min-h-screen flex"
        >
            {{-- Sidebar overlay (mobile) --}}
            <div x-show="sidebarOpen" x-transition:opacity @click="sidebarOpen = false" x-cloak class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden"></div>

            {{-- Sidebar (RTL: appears on the right) --}}
            <aside
                :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full'"
                class="fixed lg:static inset-y-0 right-0 z-50 lg:z-auto transition-all duration-300 ease-in-out flex flex-col lg:translate-x-0
                       bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 lg:border-l-0
                       shadow-2xl lg:shadow-none"
            >
                <div class="flex flex-col h-full w-72"
                     :class="collapsed ? 'lg:w-20' : 'lg:w-72'">
                    {{-- Brand row --}}
                    <div class="flex items-center gap-3 h-16 px-5 border-b border-gray-200 dark:border-gray-700 shrink-0 min-w-0">
                        <img src="{{ asset('storage/branding/logo.png') }}" alt="شعار وجهتك" class="h-9 w-9 shrink-0 rounded-xl object-contain bg-white shadow-sm" />
                        <div class="min-w-0 flex-1" x-show="!collapsed" x-cloak>
                            <div class="font-extrabold tracking-[0.2em] text-[10px] leading-none" style="color: #B97D1B;">WAJHATAK</div>
                            <div class="font-extrabold text-gray-900 dark:text-gray-100 leading-tight text-lg">وجهتك</div>
                        </div>
                        <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <x-admin.icon name="close" class="h-6 w-6" />
                        </button>
                    </div>

                    {{-- Nav --}}
                    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                        <x-admin.nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">
                            <x-slot name="icon"><x-admin.icon name="dashboard" /></x-slot>
                            لوحة التحكم
                        </x-admin.nav-link>

                        <div x-show="!collapsed" class="pt-4 text-[11px] font-bold uppercase tracking-wider text-gray-400 px-3 dark:text-gray-500" x-cloak>الإدارة</div>

                        <x-admin.nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">
                            <x-slot name="icon"><x-admin.icon name="users" /></x-slot>
                            المستخدمون
                        </x-admin.nav-link>

                        <x-admin.nav-link href="{{ route('admin.activity-logs.index') }}" :active="request()->routeIs('admin.activity-logs.*')">
                            <x-slot name="icon"><x-admin.icon name="activity" /></x-slot>
                            سجل النشاطات
                        </x-admin.nav-link>

                        <x-admin.nav-link href="{{ route('admin.locations.index') }}" :active="request()->routeIs('admin.locations.*')">
                            <x-slot name="icon"><x-admin.icon name="location" /></x-slot>
                            المواقع
                        </x-admin.nav-link>

                        <x-admin.nav-link href="{{ route('admin.agents.index') }}" :active="request()->routeIs('admin.agents.*')">
                            <x-slot name="icon"><x-admin.icon name="agents" /></x-slot>
                            الوكلاء
                        </x-admin.nav-link>

                        <x-admin.nav-link href="{{ route('admin.properties.index') }}" :active="request()->routeIs('admin.properties.*')">
                            <x-slot name="icon"><x-admin.icon name="properties" /></x-slot>
                            العقارات
                        </x-admin.nav-link>

                        <x-admin.nav-link href="{{ route('admin.viewing-requests.index') }}" :active="request()->routeIs('admin.viewing-requests.*')">
                            <x-slot name="icon"><x-admin.icon name="viewing-requests" /></x-slot>
                            طلبات المعاينة
                        </x-admin.nav-link>

                        <div x-show="!collapsed" class="pt-4 text-[11px] font-bold uppercase tracking-wider text-gray-400 px-3 dark:text-gray-500" x-cloak>المحتوى</div>

                        <x-admin.nav-link href="{{ route('admin.property-types.index') }}" :active="request()->routeIs('admin.property-types.*')">
                            <x-slot name="icon"><x-admin.icon name="property-types" /></x-slot>
                            أنواع العقارات
                        </x-admin.nav-link>

                        <x-admin.nav-link href="{{ route('admin.property-features.index') }}" :active="request()->routeIs('admin.property-features.*')">
                            <x-slot name="icon"><x-admin.icon name="property-features" /></x-slot>
                            خصائص العقارات
                        </x-admin.nav-link>

                        <x-admin.nav-link href="{{ route('admin.settings.index') }}" :active="request()->routeIs('admin.settings.*')">
                            <x-slot name="icon"><x-admin.icon name="settings" /></x-slot>
                            الإعدادات
                        </x-admin.nav-link>
                    </nav>

                    {{-- Sidebar footer: brand tagline --}}
                    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700 shrink-0">
                        <p class="text-xs text-gray-400 leading-snug dark:text-gray-500" x-show="!collapsed" x-cloak>وجهتك إلى العقار المناسب.</p>
                        <div class="text-[10px] text-gray-300 dark:text-gray-600">لوحة تحكم المشرف</div>
                    </div>
                </div>
            </aside>

            {{-- Main column --}}
            <div class="flex-1 flex flex-col min-w-0">
                {{-- Topbar --}}
                <header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                    <div class="flex items-center h-16 px-4 sm:px-6 gap-4">
                        <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <x-admin.icon name="menu" class="h-6 w-6" />
                        </button>

                        {{-- Sidebar collapse toggle (desktop) --}}
                        <button
                            @click="collapsed = !collapsed"
                            class="hidden lg:inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 me-0 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800"
                            title="طي / توسيع القائمة الجانبية"
                            aria-label="إظهار أو إخفاء القائمة الجانبية"
                        >
                            <x-admin.icon x-show="!collapsed" name="collapse-right" class="h-5 w-5" />
                            <x-admin.icon x-show="collapsed" name="collapse-left" class="h-5 w-5" />
                        </button>

                        <h1 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-gray-100">{{ $heading }}</h1>

                        @isset($breadcrumbs)
                            <nav class="hidden md:flex items-center text-sm text-gray-400 ms-auto dark:text-gray-500">
                                @foreach($breadcrumbs as $crumb)
                                    @if(! $loop->last)
                                        <a href="{{ $crumb['url'] }}" class="hover:text-wajhatak-600">{{ $crumb['label'] }}</a>
                                        <span class="mx-2">‹</span>
                                    @else
                                        <span class="text-gray-600 dark:text-gray-300">{{ $crumb['label'] }}</span>
                                    @endif
                                @endforeach
                            </nav>
                        @endisset

                        <div class="ms-auto flex items-center gap-2">
                            <x-admin.theme-switcher theme="system" />

                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = ! open" class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-200 dark:hover:text-white">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full text-white font-bold brand-mark">
                                        {{ mb_substr(Auth::user()->name, 0, 1) }}
                                    </span>
                                    <span class="hidden sm:block">{{ Auth::user()->name }}</span>
                                    <x-admin.icon name="chevron-down" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                </button>

                                <div x-show="open" x-transition @click.outside="open = false" x-cloak class="absolute left-0 mt-2 w-56 rounded-2xl shadow-xl bg-white ring-1 ring-black/5 z-50 dark:bg-gray-800 dark:ring-gray-700">
                                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                        <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ Auth::user()->name }}</div>
                                        <div class="text-xs text-gray-500 truncate dark:text-gray-400">{{ Auth::user()->email }}</div>
                                    </div>
                                    <div class="py-1.5">
                                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700">الملف الشخصي</a>
                                        <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                                        <form method="POST" action="{{ route('logout') }}" class="block">
                                            @csrf
                                            <button type="submit" class="w-full text-right px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">تسجيل الخروج</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                {{-- Main content --}}
                <main class="flex-1 p-4 sm:p-6">
                    @if(session('status'))
                        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900 dark:bg-green-900/30 dark:text-green-300">{{ session('status') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-900/30 dark:text-red-300">{{ session('error') }}</div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    @stack('scripts')
    </body>
</html>

