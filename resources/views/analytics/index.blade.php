@extends('layouts.app', ['title' => 'الإحصائيات'])
@section('content')
<div class="mb-8"><p class="mb-2 text-sm font-bold text-gold">متابعة الأداء</p><h1 class="text-3xl font-bold text-emerald-950">الإحصائيات والتحليلات</h1><p class="mt-2 text-slate-500">نظرة عملية على تقدم الطلاب في كل قراءة.</p></div>
<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4"><div class="rounded-3xl bg-emerald-950 p-6 text-white"><i class="bx bx-group text-3xl text-gold"></i><div class="mt-5 text-3xl font-bold">{{ $studentCount }}</div><p class="mt-1 text-emerald-100/60">إجمالي الطلاب</p></div><div class="rounded-3xl border border-slate-200 bg-white p-6"><i class="bx bx-play-circle text-3xl text-emerald-700"></i><div class="mt-5 text-3xl font-bold text-emerald-950">{{ $sessionCount }}</div><p class="mt-1 text-slate-400">جلسات التسميع</p></div><div class="rounded-3xl border border-slate-200 bg-white p-6"><i class="bx bx-check-circle text-3xl text-gold"></i><div class="mt-5 text-3xl font-bold text-emerald-950">{{ $ayahCount }}</div><p class="mt-1 text-slate-400">إجمالي تأكيدات التسميع</p></div><div class="rounded-3xl border border-slate-200 bg-white p-6"><i class="bx bx-calendar-check text-3xl text-emerald-700"></i><div class="mt-5 text-lg font-bold text-emerald-950">{{ $lastActivity?->confirmed_at?->locale('ar')->translatedFormat('j F Y') ?? 'لا يوجد نشاط' }}</div><p class="mt-1 text-slate-400">آخر نشاط</p></div></div>
<div class="mt-8 rounded-3xl border border-slate-200 bg-white p-5 sm:p-7"><div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"><div><h2 class="text-xl font-bold text-emerald-950">تقدم الطلاب حسب القراءات</h2><p class="mt-1 text-sm text-slate-400">اسحب أفقيًا لرؤية جميع القراءات على الهاتف. النسبة محسوبة من أصل 6236 آية.</p></div><div class="grid gap-3 sm:grid-cols-3 lg:min-w-[620px]"><label class="relative"><i class="bx bx-search absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i><input id="student-search" type="search" placeholder="ابحث باسم الطالب" class="w-full rounded-2xl border border-slate-200 bg-sand py-3 pr-10 pl-3 text-sm outline-none focus:border-emerald-700"></label><select id="reading-filter" class="rounded-2xl border border-slate-200 bg-sand px-3 py-3 text-sm outline-none focus:border-emerald-700"><option value="all">كل القراءات</option>@foreach($qiraats as $qiraat)<option value="{{ $qiraat->id }}">{{ $qiraat->name }}</option>@endforeach</select><select id="progress-filter" class="rounded-2xl border border-slate-200 bg-sand px-3 py-3 text-sm outline-none focus:border-emerald-700"><option value="all">كل الحالات</option><option value="started">بدأ التسميع</option><option value="not-started">لم يبدأ</option><option value="complete">مكتمل</option></select></div></div><div id="students-list" class="space-y-7">@forelse($students as $student)@php($hasProgress = $student->reading_progress->contains(fn ($reading) => $reading->saved_ayahs > 0)) @php($isComplete = $student->reading_progress->contains(fn ($reading) => $reading->percentage >= 100))<a href="{{ route('analytics.student', $student) }}" data-student-row data-name="{{ mb_strtolower($student->name) }}" data-has-progress="{{ $hasProgress ? '1' : '0' }}" data-complete="{{ $isComplete ? '1' : '0' }}" class="group block rounded-2xl p-3 transition hover:bg-sand"><div class="mb-4 flex items-center justify-between gap-4"><div class="flex items-center gap-3"><div class="grid h-11 w-11 place-items-center rounded-full bg-emerald-100 font-bold text-emerald-800">{{ mb_substr($student->name, 0, 1) }}</div><span class="font-bold text-emerald-950">{{ $student->name }}</span></div><i class="bx bx-left-arrow-alt text-xl text-gold transition group-hover:-translate-x-1"></i></div><div data-readings-row class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-3">@foreach($student->reading_progress as $reading)<div data-reading-card data-reading-id="{{ $reading->id }}" class="min-w-[116px] shrink-0 snap-start text-center"><div class="mx-auto grid h-20 w-20 place-items-center rounded-full" style="background: conic-gradient(#c99b4a {{ $reading->percentage }}%, #e8e8e8 0)"><div class="grid h-16 w-16 place-items-center rounded-full bg-white text-xs font-bold text-emerald-950">{{ $reading->percentage }}%</div></div><p class="mt-2 truncate text-xs font-bold text-slate-600" title="{{ $reading->name }}">{{ $reading->name }}</p><p class="text-[11px] text-slate-400">{{ $reading->saved_ayahs }} / 6236</p></div>@endforeach</div></a>@empty<div class="py-8 text-center text-slate-400">لا توجد بيانات بعد.</div>@endforelse<div id="no-results" class="hidden py-8 text-center text-slate-400">لا توجد نتائج مطابقة للبحث والتصفية.</div></div></div>
<script>
(() => {
    const search = document.getElementById('student-search');
    const reading = document.getElementById('reading-filter');
    const progress = document.getElementById('progress-filter');
    const rows = [...document.querySelectorAll('[data-student-row]')];
    const empty = document.getElementById('no-results');
    const filter = () => {
        const query = search.value.trim().toLocaleLowerCase('ar');
        const readingId = reading.value;
        const status = progress.value;
        let visible = 0;
        rows.forEach((row) => {
            const matchesName = !query || row.dataset.name.includes(query);
            const cards = [...row.querySelectorAll('[data-reading-card]')];
            let matchesReading = false;
            cards.forEach((card) => {
                const matches = readingId === 'all' || card.dataset.readingId === readingId;
                card.classList.toggle('hidden', !matches);
                if (matches) matchesReading = true;
            });
            const matchesStatus = status === 'all' || (status === 'started' && row.dataset.hasProgress === '1') || (status === 'not-started' && row.dataset.hasProgress === '0') || (status === 'complete' && row.dataset.complete === '1');
            const show = matchesName && matchesReading && matchesStatus;
            row.classList.toggle('hidden', !show);
            if (show) visible++;
        });
        empty.classList.toggle('hidden', visible !== 0);
    };
    [search, reading, progress].forEach((control) => control.addEventListener('input', filter));
    [reading, progress].forEach((control) => control.addEventListener('change', filter));
})();
</script>
@endsection
