<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'وجهتك') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('storage/branding/logo-small.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Cairo', ui-sans-serif, system-ui, sans-serif; }

            .brand-mark {
                background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E);
                box-shadow: 0 4px 14px rgba(14, 138, 109, 0.3);
            }

            .auth-backdrop {
                background:
                    radial-gradient(1200px 800px at 90% -10%, rgba(14, 138, 109, 0.18), transparent 60%),
                    radial-gradient(1000px 700px at 0% 110%, rgba(237, 168, 60, 0.14), transparent 55%),
                    linear-gradient(135deg, #F5F8F7 0%, #EDF7F3 50%, #D5F3E9 100%);
            }

            .dark .auth-backdrop,
            .dark body {
                background:
                    radial-gradient(1200px 800px at 90% -10%, rgba(14, 138, 109, 0.25), transparent 60%),
                    radial-gradient(1000px 700px at 0% 110%, rgba(237, 168, 60, 0.12), transparent 55%),
                    linear-gradient(135deg, #0A1512 0%, #10201B 50%, #0D1B16 100%);
            }

            .auth-card {
                background: #ffffff;
                box-shadow: 0 12px 40px rgba(14, 138, 109, 0.12);
            }
            .dark .auth-card {
                background: #10201B;
                box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        @php
            $siteName = \App\Models\Setting::get('site_name', 'وجهتك');
            $siteTagline = \App\Models\Setting::get('site_tagline', 'وجهتك إلى العقار المناسب.');
            $siteLogo = public_path('storage/branding/logo.png');
        @endphp
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 py-10 auth-backdrop">
            <div>
                <a href="/" class="flex flex-col items-center">
                    <div class="relative flex h-24 w-24 items-center justify-center">
                        @if (file_exists($siteLogo))
                            <img src="{{ asset('storage/branding/logo.png') }}" alt="شعار {{ $siteName }}" class="h-24 w-24 rounded-3xl object-contain drop-shadow-xl" />
                        @else
                            <span class="brand-mark flex h-20 w-20 items-center justify-center rounded-3xl text-4xl font-black text-white">و</span>
                        @endif
                    </div>
                    <div class="mt-4 text-center leading-tight">
                        <div class="text-sm font-extrabold tracking-[0.25em]" style="color: #B97D1B;">WAJHATAK</div>
                        <div class="text-2xl font-extrabold text-gray-900 dark:text-gray-100">{{ $siteName }}</div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $siteTagline }}</p>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 auth-card rounded-3xl px-6 py-8 sm:px-8 sm:py-8">
                {{ $slot }}
            </div>

            <p class="mt-6 text-xs text-gray-400 dark:text-gray-500">© {{ date('Y') }} {{ $siteName }} — لوحة تحكم الإدارة</p>
        </div>
    </body>
</html>
