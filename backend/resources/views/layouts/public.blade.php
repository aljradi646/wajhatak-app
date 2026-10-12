<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="@yield('meta_description', 'مركز وجهتك الرسمي للمعلومات والخصوصية والمساعدة والتواصل.')"><meta name="theme-color" content="#f7faf8">
<title>@yield('title', 'مركز المعلومات والمساعدة') — {{ $siteName ?? 'وجهتك' }}</title>
<link rel="preconnect" href="https://fonts.bunny.net"><link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&display=swap" rel="stylesheet">
@vite(['resources/css/app.css','resources/js/app.js'])
<style>
:root{color-scheme:light;--g:#087f62;--deep:#075b49;--ink:#172c26;--muted:#62736c;--line:#e1e9e4;--bg:#f7faf8}
html{scroll-behavior:smooth;scroll-padding-top:7rem}body{font-family:'Cairo',ui-sans-serif,system-ui,sans-serif;color:var(--ink);background:var(--bg)}
.wj-container{width:min(1160px,calc(100% - 40px));margin-inline:auto}.wj-brand{background:linear-gradient(135deg,#075b49,#0b8e6c)}
.wj-focus:focus-visible{outline:3px solid #87d9bf;outline-offset:3px}.wj-card{border:1px solid var(--line);background:#fff;border-radius:22px;transition:border-color .2s,box-shadow .2s,transform .2s}.wj-card:hover{border-color:#c7ded4;box-shadow:0 14px 34px #13433212}
.wj-prose p{margin:0 0 1.15rem;line-height:2.05;color:#465951}.wj-prose p:last-child{margin-bottom:0}.wj-prose strong{color:#173a2f;font-weight:800}
.wj-faq[open] .wj-faq-chevron{transform:rotate(180deg)}.wj-faq-chevron{transition:transform .2s}
.wj-input{width:100%;border:1px solid #d7e2dc;border-radius:14px;background:#fff;padding:.85rem 1rem;color:#203b31;outline:none;transition:border-color .15s,box-shadow .15s}.wj-input:focus{border-color:#0b8d6b;box-shadow:0 0 0 4px #0b8d6b1c}
.wj-label{display:block;font-size:.88rem;font-weight:800;color:#30483d;margin-bottom:.45rem}
@media(max-width:640px){.wj-container{width:min(calc(100% - 28px),1160px)}}
</style>
@stack('head')
</head>
<body class="min-h-screen antialiased">
<header class="sticky top-0 z-40 border-b border-[#e5ece7]/90 bg-white/90 backdrop-blur-xl">
<div class="wj-container flex min-h-[76px] items-center justify-between gap-5">
<a href="{{ route('public.pages.index') }}" class="wj-focus flex shrink-0 items-center gap-3 rounded-xl" aria-label="وجهتك — مركز المعلومات والمساعدة"><span class="wj-brand flex h-11 w-11 items-center justify-center rounded-2xl text-xl font-black text-white shadow-sm">و</span><span><span class="block text-lg font-extrabold leading-tight text-[#15372b]">{{ $siteName ?? 'وجهتك' }}</span><span class="mt-1 block text-[11px] font-bold tracking-wide text-[#73847b]">مركز المعلومات والمساعدة</span></span></a>
<nav class="hidden items-center gap-1 md:flex" aria-label="التنقل الرئيسي"><a class="wj-focus rounded-full px-4 py-2.5 text-sm font-bold text-[#52665c] hover:bg-[#f2f7f4] hover:text-[#087f62]" href="{{ route('public.pages.index') }}">مركز المساعدة</a><a class="wj-focus rounded-full px-4 py-2.5 text-sm font-bold text-[#52665c] hover:bg-[#f2f7f4] hover:text-[#087f62]" href="{{ route('public.pages.index') }}#policies">السياسات والشروط</a><a class="wj-focus rounded-full px-4 py-2.5 text-sm font-bold text-[#52665c] hover:bg-[#f2f7f4] hover:text-[#087f62]" href="{{ route('public.pages.show','about') }}">عن وجهتك</a></nav>
<a href="{{ route('public.pages.show','contact') }}" class="wj-focus inline-flex shrink-0 items-center gap-2 rounded-full bg-[#087f62] px-4 py-2.5 text-sm font-extrabold text-white shadow-sm transition hover:bg-[#066a52]"><span class="hidden sm:inline">تواصل معنا</span><span class="sm:hidden">تواصل</span><x-public.icon name="message" class="h-4 w-4"/></a>
</div>
<nav class="wj-container flex gap-2 overflow-x-auto pb-3 md:hidden" aria-label="التنقل السريع"><a class="whitespace-nowrap rounded-full bg-[#f0f6f2] px-3 py-2 text-xs font-bold text-[#2e5746]" href="{{ route('public.pages.index') }}">مركز المساعدة</a><a class="whitespace-nowrap rounded-full px-3 py-2 text-xs font-bold text-[#52665c] hover:bg-[#f0f6f2]" href="{{ route('public.pages.index') }}#policies">السياسات والشروط</a><a class="whitespace-nowrap rounded-full px-3 py-2 text-xs font-bold text-[#52665c] hover:bg-[#f0f6f2]" href="{{ route('public.pages.show','faq') }}">الأسئلة الشائعة</a><a class="whitespace-nowrap rounded-full px-3 py-2 text-xs font-bold text-[#52665c] hover:bg-[#f0f6f2]" href="{{ route('public.pages.show','contact') }}">اتصل بنا</a></nav>
</header>
<main class="min-h-[70vh]">@yield('content')</main>
<footer class="mt-20 border-t border-[#e2eae5] bg-white"><div class="wj-container grid gap-10 py-12 md:grid-cols-[1.4fr_1fr_1fr]">
<div><a href="{{ route('public.pages.index') }}" class="inline-flex items-center gap-3"><span class="wj-brand flex h-10 w-10 items-center justify-center rounded-xl font-black text-white">و</span><span class="text-lg font-extrabold text-[#18382c]">{{ $siteName ?? 'وجهتك' }}</span></a><p class="mt-4 max-w-sm text-sm leading-7 text-[#66776e]">كل ما تحتاج معرفته عن استخدام وجهتك، وحماية خصوصيتك، والتواصل مع فريق الدعم.</p></div>
<div><h2 class="text-sm font-extrabold text-[#193b2e]">المعلومات والسياسات</h2><div class="mt-4 grid gap-3 text-sm text-[#62736a]"><a class="hover:text-[#087f62]" href="{{ route('public.pages.show','privacy') }}">سياسة الخصوصية</a><a class="hover:text-[#087f62]" href="{{ route('public.pages.show','terms') }}">الشروط والأحكام</a><a class="hover:text-[#087f62]" href="{{ route('public.pages.show','usage-policy') }}">سياسة الاستخدام</a><a class="hover:text-[#087f62]" href="{{ route('public.pages.show','cookies') }}">ملفات تعريف الارتباط</a><a class="hover:text-[#087f62]" href="{{ route('public.pages.show','refund-cancellation') }}">الإلغاء والاسترداد</a></div></div>
<div><h2 class="text-sm font-extrabold text-[#193b2e]">المساعدة والتواصل</h2><div class="mt-4 grid gap-3 text-sm text-[#62736a]"><a class="hover:text-[#087f62]" href="{{ route('public.pages.show','faq') }}">الأسئلة الشائعة</a><a class="hover:text-[#087f62]" href="{{ route('public.pages.show','how-to-use') }}">دليل الاستخدام</a><a class="hover:text-[#087f62]" href="{{ route('public.pages.show','contact') }}">اتصل بنا</a><a class="hover:text-[#087f62]" href="{{ route('public.pages.show','about') }}">من نحن</a></div></div></div>
<div class="border-t border-[#edf1ee]"><div class="wj-container flex flex-col gap-2 py-5 text-xs text-[#7b8981] sm:flex-row sm:items-center sm:justify-between"><span>© {{ now()->year }} {{ $siteName ?? 'وجهتك' }}. جميع الحقوق محفوظة.</span><span>معلومات واضحة، وخصوصية محترمة، ودعم يسهل الوصول إليه.</span></div></div></footer>
@stack('scripts')
</body></html>