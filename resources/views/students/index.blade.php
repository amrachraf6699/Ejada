@extends('layouts.app', ['title' => 'الطلاب'])
@section('content')
    <div class="mb-8 flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
        <div>
            <div class="mb-2 text-sm font-bold text-gold">إدارة الحلقة</div>
            <h1 class="text-3xl font-extrabold text-emerald-950">الطلاب</h1>
            <p class="mt-2 text-slate-500">قائمة الطلاب المسجلين في حلقة التحفيظ.</p>
        </div><a href="{{ route('students.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-950 px-5 py-3.5 font-bold text-white shadow-lg"><i
                class="bx bx-plus text-xl text-gold"></i> إضافة طالب</a>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <form method="GET" class="mb-6 flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1"><i
                    class="bx bx-search pointer-events-none absolute right-4 top-3.5 text-lg text-slate-400"></i><input
                    name="search" value="{{ request('search') }}" placeholder="ابحث باسم الطالب..."
                    class="w-full rounded-2xl border border-slate-200 bg-sand px-11 py-3.5 text-sm outline-none focus:border-emerald-700">
            </div><button
                class="rounded-2xl border border-emerald-950 px-6 py-3 font-bold text-emerald-950 transition hover:bg-emerald-50">بحث</button>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[540px] text-right">
                <thead>
                    <tr class="border-b border-slate-100 text-sm text-slate-400">
                        <th class="px-4 py-4 font-bold">#</th>
                        <th class="px-4 py-4 font-bold">اسم الطالب</th>
                        <th class="px-4 py-4 font-bold">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>@forelse($students as $student)<tr class="border-b border-slate-100 last:border-0">
                    <td class="px-4 py-5 text-sm text-slate-400">{{ $students->firstItem() + $loop->index }}</td>
                    <td class="px-4 py-5">
                        <div class="flex items-center gap-3">
                            <div
                                class="grid h-10 w-10 place-items-center rounded-full bg-emerald-100 font-bold text-emerald-800">
                                {{ mb_substr($student->name, 0, 1) }}</div><span
                                class="font-bold">{{ $student->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-5">
                        <div class="flex items-center gap-2"><a href="{{ route('students.edit', $student) }}"
                                class="inline-flex items-center gap-1 rounded-xl bg-amber-50 px-3 py-2 text-sm font-bold text-amber-700 hover:bg-amber-100">تعديل
                                <i class="bx bx-edit-alt"></i></a>
                            <form method="POST" action="{{ route('students.destroy', $student) }}"
                                data-confirm="هل تريد حذف هذا الطالب؟">@csrf @method('DELETE')<button
                                    class="inline-flex items-center gap-1 rounded-xl bg-red-50 px-3 py-2 text-sm font-bold text-red-600 hover:bg-red-100">حذف
                                    <i class="bx bx-trash"></i></button></form>
                        </div>
                    </td>
                </tr>@empty<tr>
                        <td colspan="3" class="py-16 text-center text-slate-400">لا توجد نتائج مطابقة.</td>
                    </tr>@endforelse</tbody>
            </table>
        </div>@if($students->hasPages())
        <div class="mt-6 border-t border-slate-100 pt-5">{{ $students->links() }}</div>@endif
    </div>
@endsection