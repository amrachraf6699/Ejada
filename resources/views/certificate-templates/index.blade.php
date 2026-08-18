@extends('layouts.app', ['title' => 'قوالب الشهادات'])
@section('content')
<style>[x-cloak]{display:none!important}</style>
<div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div><p class="mb-2 text-sm font-bold text-gold">إدارة التصميمات</p><h1 class="text-3xl font-bold text-emerald-950">قوالب الشهادات</h1><p class="mt-2 text-slate-500">أنشئ قوالب شهادات قابلة لإصدارها للطلاب.</p></div>
    <a href="{{ route('certificate-templates.create') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-950 px-5 py-3.5 font-bold text-white"><i class="bx bx-plus text-xl text-gold"></i> قالب جديد</a>
</div>

<div x-data="{ cloneOpen: false, cloneAction: '', cloneName: '' }" @keydown.escape.window="cloneOpen = false">
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($templates as $template)
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <img src="{{ Storage::disk('public')->url($template->background_path) }}" alt="{{ $template->name }}" class="aspect-[4/3] w-full object-cover">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div><h2 class="font-bold text-emerald-950">{{ $template->name }}</h2><p class="mt-1 text-sm text-slate-400">{{ $template->width }} × {{ $template->height }} بكسل</p></div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">نشط</span>
                    </div>
                    <div class="mt-5 grid grid-cols-3 gap-2">
                        <a href="{{ route('certificate-templates.edit', $template) }}" class="rounded-xl bg-amber-50 py-2 text-center text-sm font-bold text-amber-700">تعديل</a>
                        <button type="button" data-clone-action="{{ route('certificate-templates.clone', $template) }}" data-template-name="{{ $template->name }}" @click="cloneAction = $el.dataset.cloneAction; cloneName = $el.dataset.templateName + ' - نسخة'; cloneOpen = true; $nextTick(() => $refs.cloneName.focus())" class="rounded-xl bg-emerald-50 py-2 text-sm font-bold text-emerald-700">نسخ</button>
                        <form method="POST" action="{{ route('certificate-templates.destroy', $template) }}" data-confirm="هل تريد حذف هذا القالب؟">@csrf @method('DELETE')<button class="w-full rounded-xl bg-red-50 py-2 text-sm font-bold text-red-600">حذف</button></form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white p-14 text-center text-slate-400">لا توجد قوالب بعد.</div>
        @endforelse
    </div>

    <div x-cloak x-show="cloneOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-emerald-950/50 p-4" @click.self="cloneOpen = false" role="dialog" aria-modal="true" aria-labelledby="clone-template-title">
        <form method="POST" :action="cloneAction" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl sm:p-8">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div><h2 id="clone-template-title" class="text-2xl font-bold text-emerald-950">نسخ قالب الشهادة</h2><p class="mt-2 text-slate-500">اكتب اسمًا للنسخة الجديدة.</p></div>
                <button type="button" @click="cloneOpen = false" class="text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="إغلاق">×</button>
            </div>
            <label class="mt-6 block text-sm font-bold text-emerald-950">اسم القالب<input x-ref="cloneName" x-model="cloneName" name="name" required maxlength="255" class="mt-2 w-full rounded-xl border border-slate-200 bg-sand px-4 py-3 text-base focus:border-gold focus:outline-none focus:ring-2 focus:ring-gold/30"></label>
            @error('name')<p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>@enderror
            <div class="mt-6 flex gap-3"><button type="button" @click="cloneOpen = false" class="flex-1 rounded-xl border border-slate-200 py-3 font-bold text-slate-600">إلغاء</button><button class="flex-1 rounded-xl bg-emerald-950 py-3 font-bold text-white">إنشاء النسخة</button></div>
        </form>
    </div>
</div>
@endsection
