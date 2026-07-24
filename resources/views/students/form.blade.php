<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf @if($method !== 'POST') @method($method) @endif
    <div>
        <label for="name" class="mb-2 block text-sm font-bold">اسم الطالب</label><input id="name" name="name"
            value="{{ old('name', $student->name ?? '') }}" required autofocus placeholder="مثال: عبد الرحمن محمد"
            class="w-full rounded-2xl border border-slate-200 bg-sand px-4 py-4 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">@error('name')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end"><a
            href="{{ route('students.index') }}"
            class="rounded-2xl px-5 py-3 text-center font-bold text-slate-500 hover:bg-slate-50">إلغاء</a><button
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-950 px-6 py-3 font-bold text-white shadow-lg shadow-emerald-950/15 transition hover:bg-emerald-800">{{ $button }}
            <i class="bx bx-left-arrow-alt text-lg text-gold"></i></button></div>
</form>