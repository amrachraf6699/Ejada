@extends('layouts.app', ['title' => 'تعديل قالب الشهادة'])
@section('content')
<div class="mb-6"><a href="{{ route('certificate-templates.index') }}" class="inline-flex items-center gap-1 text-sm font-bold text-slate-500"><i class="bx bx-right-arrow-alt"></i> القوالب</a></div>
<div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8"><div class="mb-8"><p class="mb-2 text-sm font-bold text-gold">تحديث التصميم</p><h1 class="text-3xl font-bold text-emerald-950">تعديل قالب الشهادة</h1></div>@include('certificate-templates.editor', ['action' => route('certificate-templates.update', $template), 'method' => 'PUT', 'initialJson' => $template->canvas_json])</div>
@endsection
