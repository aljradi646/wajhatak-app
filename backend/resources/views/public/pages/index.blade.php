@extends('layouts.public')
@section('title','السياسات والمعلومات')
@section('content')
<section class="public-hero overflow-hidden rounded-3xl p-7 text-white shadow-xl md:p-10"><p class="text-sm font-bold text-emerald-100">مركز المعلومات الرسمي</p><h1 class="mt-2 text-3xl font-black md:text-4xl">السياسات، الإرشادات والدعم</h1><p class="mt-3 max-w-2xl leading-8 text-emerald-50">المرجع العام لشروط استخدام وجهتك وخصوصية البيانات وطريقة استعمال الخدمات.</p></section>
<div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
@foreach($pages as $page)<a href="{{ route('public.pages.show',$page->slug) }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-emerald-300 hover:shadow-lg"><span class="inline-flex rounded-xl px-3 py-1 text-xs font-extrabold {{ $page->category==='legal'?'bg-violet-50 text-violet-700':($page->category==='help'?'bg-amber-50 text-amber-700':'bg-emerald-50 text-emerald-700') }}">{{ $page->category==='legal'?'سياسة وشروط':($page->category==='help'?'مساعدة':'معلومات') }}</span><h2 class="mt-4 text-lg font-extrabold text-slate-900">{{ $page->title }}</h2><p class="mt-2 leading-7 text-slate-600">{{ $page->summary }}</p><span class="mt-5 inline-flex items-center gap-2 text-sm font-extrabold text-emerald-700">قراءة الصفحة ←</span></a>@endforeach
</div>@endsection
