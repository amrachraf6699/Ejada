@extends('layouts.app', ['title' => 'تعديل طالب'])
@section('content')
    <div class="mx-auto max-w-2xl"><a href="{{ route('students.index') }}"
            class="mb-6 inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-emerald-800"><i
                class="bx bx-right-arrow-alt text-lg"></i> العودة إلى الطلاب</a>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-8">
                <div class="mb-2 text-sm font-bold text-gold">تحديث بيانات الطالب</div>
                <h1 class="text-3xl font-extrabold text-emerald-950">تعديل الطالب</h1>
                <p class="mt-2 text-slate-500">حدّث اسم الطالب ثم احفظ التغييرات.</p>
            </div>
            @include('students.form', ['action' => route('students.update', $student), 'method' => 'PUT', 'button' => 'حفظ التغييرات'])
        </div>
    </div>
@endsection