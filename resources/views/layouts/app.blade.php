<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'المحفظ' }} | المحفظ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Amiri','serif']},colors:{emerald:{950:'#062c26'},sand:'#f8f5ee',gold:'#c99b4a'}}}};</script>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body{font-family:'Amiri',serif;background:#f8f5ee}.swal2-container.swal2-top-start{left:1rem;right:auto}
        .islamic-pattern{background-image:linear-gradient(30deg,#ffffff12 12%,transparent 12.5%,transparent 87%,#ffffff12 87.5%,#ffffff12),linear-gradient(150deg,#ffffff12 12%,transparent 12.5%,transparent 87%,#ffffff12 87.5%,#ffffff12);background-size:32px 56px}
    </style>
</head>
<body class="text-slate-800 antialiased" x-data="{sidebarOpen:false}">
<div class="min-h-screen lg:flex">
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-emerald-950/50 lg:hidden" @click="sidebarOpen=false"></div>
    <aside class="fixed inset-y-0 right-0 z-40 flex w-72 translate-x-full flex-col bg-emerald-950 text-white shadow-2xl transition-transform lg:static lg:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full'">
        <div class="islamic-pattern border-b border-white/10 px-7 py-7"><div class="flex items-center gap-3"><div class="grid h-11 w-11 place-items-center rounded-2xl bg-gold text-emerald-950"><i class="bx bx-book-open text-2xl"></i></div><div><div class="text-2xl font-extrabold">المحفظ</div><div class="text-xs text-emerald-100/60">رفيقك في طريق الحفظ</div></div></div></div>
        <nav class="flex-1 space-y-2 px-4 py-7"><div class="mb-4 px-3 text-xs font-bold text-emerald-100/40">القائمة الرئيسية</div>
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3.5 text-sm font-bold transition {{ request()->routeIs('dashboard') ? 'bg-white/10 text-gold' : 'text-emerald-50/70 hover:bg-white/5 hover:text-white' }}"><i class="bx bx-home-alt-2 text-xl"></i>بدء جلسة</a>
            <a href="{{ route('students.index') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3.5 text-sm font-bold transition {{ request()->routeIs('students.*') ? 'bg-white/10 text-gold' : 'text-emerald-50/70 hover:bg-white/5 hover:text-white' }}"><i class="bx bx-group text-xl"></i>الطلاب</a>
            <a href="{{ route('certificate-templates.index') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3.5 text-sm font-bold transition {{ request()->routeIs('certificate-templates.*') ? 'bg-white/10 text-gold' : 'text-emerald-50/70 hover:bg-white/5 hover:text-white' }}"><i class="bx bx-certification text-xl"></i>قوالب الشهادات</a>
            <a href="{{ route('certificates.create') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3.5 text-sm font-bold transition {{ request()->routeIs('certificates.create') ? 'bg-white/10 text-gold' : 'text-emerald-50/70 hover:bg-white/5 hover:text-white' }}"><i class="bx bx-award text-xl"></i>إصدار شهادة</a>
            <a href="{{ route('certificates.index') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3.5 text-sm font-bold transition {{ request()->routeIs('certificates.index') ? 'bg-white/10 text-gold' : 'text-emerald-50/70 hover:bg-white/5 hover:text-white' }}"><i class="bx bx-history text-xl"></i>الشهادات الصادرة</a>
            <a href="{{ route('analytics') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3.5 text-sm font-bold transition {{ request()->routeIs('analytics*') ? 'bg-white/10 text-gold' : 'text-emerald-50/70 hover:bg-white/5 hover:text-white' }}"><i class="bx bx-bar-chart-alt-2 text-xl"></i>الإحصائيات</a>
        </nav>
        <div class="border-t border-white/10 p-5"><div class="mb-4 flex items-center gap-3"><div class="grid h-10 w-10 place-items-center rounded-full bg-gold/20 text-gold"><i class="bx bx-user text-xl"></i></div><div class="min-w-0"><div class="truncate text-sm font-bold">{{ auth()->user()->name }}</div><div class="truncate text-xs text-emerald-100/50">مدير النظام</div></div></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 py-2.5 text-sm font-bold text-emerald-100/70 transition hover:bg-white/10 hover:text-white">تسجيل الخروج <i class="bx bx-log-out text-lg"></i></button></form></div>
    </aside>
    <main class="min-w-0 flex-1"><header class="flex h-20 items-center justify-between border-b border-emerald-950/5 bg-white/70 px-5 backdrop-blur sm:px-8"><div class="flex items-center gap-4"><button aria-label="فتح القائمة" class="grid h-11 w-11 place-items-center rounded-xl border border-slate-200 text-2xl lg:hidden" @click="sidebarOpen=true"><i class="bx bx-menu"></i></button><div><div class="text-sm text-slate-400">{{ now()->locale('ar')->translatedFormat('l، j F Y') }}</div><div class="font-bold text-emerald-950">السلام عليكم، {{ auth()->user()->name }}</div></div></div><div class="hidden h-11 w-11 place-items-center rounded-full bg-emerald-100 font-bold text-emerald-900 sm:grid"><i class="bx bx-user text-xl"></i></div></header><div class="mx-auto max-w-7xl p-5 sm:p-8">@yield('content')</div></main>
</div>
@if(session('success'))<script>window.addEventListener('DOMContentLoaded',()=>Swal.fire({toast:true,position:'top-start',icon:'success',title:@json(session('success')),showConfirmButton:false,timer:3200,timerProgressBar:true}));</script>@endif
</body>
</html>
