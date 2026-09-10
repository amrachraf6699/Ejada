@extends('layouts.app', ['title' => 'بدء جلسة'])
@section('content')
    <div class="mx-auto max-w-5xl">
        <div class="mb-8 grid gap-5 sm:grid-cols-2"><div class="rounded-3xl bg-emerald-950 p-6 text-white"><i class="bx bx-check-circle text-3xl text-gold"></i><div class="mt-4 text-3xl font-bold">{{ $completedAttempts }}</div><p class="mt-1 text-emerald-100/60">إجمالي مرات إتمام الروايات</p></div><div class="rounded-3xl border border-slate-200 bg-white p-6"><i class="bx bx-play-circle text-3xl text-emerald-700"></i><div class="mt-4 text-3xl font-bold text-emerald-950">{{ $activeAttempts }}</div><p class="mt-1 text-slate-400">محاولات تسميع نشطة</p></div></div>
    <div class="flex min-h-[calc(100vh-18rem)] flex-col items-center justify-center text-center">
        <div
            class="mb-8 grid h-24 w-24 place-items-center rounded-[2rem] bg-emerald-950 text-5xl text-gold shadow-xl shadow-emerald-950/20">
            <i class="bx bx-book-open"></i>
        </div>
        <p class="text-4xl mb-8 font-bold text-gold sm:text-5xl">بسم الله نبدأ</p>
        <h1 class="text-sm font-bold text-emerald-950 ">{{ now()->locale('ar')->translatedFormat('l، j F Y') }}</h1>
        <a href="{{ route('session.students') }}"
            class="mt-4 grid h-44 w-44 place-items-center rounded-full bg-emerald-950 text-4xl font-bold text-gold shadow-2xl shadow-emerald-950/25 ring-8 ring-emerald-950/5 transition hover:scale-105 hover:bg-emerald-800">
            بدء الجلسة
        </a>
    </div>
    <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6"><h2 class="text-xl font-bold text-emerald-950">آخر الإتمامات</h2><div class="mt-4 space-y-3">@forelse($recentCompletions as $attempt)<div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3 last:border-0"><span class="font-bold text-emerald-950">{{ $attempt->student->name }} · {{ $attempt->narration->qiraat->name }} · رواية {{ $attempt->narration->name }}</span><span class="text-sm text-slate-500">المرة {{ $attempt->attempt_number }} · {{ $attempt->completed_at->locale('ar')->translatedFormat('j F Y') }}</span></div>@empty<p class="py-4 text-slate-400">لا توجد إتمامات بعد.</p>@endforelse</div></section>
    </div>
@endsection
