@extends('layouts.app', ['title' => 'إضافة طالب'])
@section('content')
    <div class="mx-auto max-w-2xl"><a href="{{ route('students.index') }}"
            class="mb-6 inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-emerald-800"><i
                class="bx bx-right-arrow-alt text-lg"></i> العودة إلى الطلاب</a>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-8">
                <div class="mb-2 text-sm font-bold text-gold">سجل طالب جديد</div>
                <h1 class="text-3xl font-extrabold text-emerald-950">إضافة طالب</h1>
                <p class="mt-2 text-slate-500">أدخل اسم الطالب لإضافته إلى الحلقة.</p>
            </div>
            @include('students.form', ['action' => route('students.store'), 'method' => 'POST', 'button' => 'إضافة الطالب'])
        </div>
    </div>
@endsection