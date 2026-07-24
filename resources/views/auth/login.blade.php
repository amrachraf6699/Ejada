<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | المحفظ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Amiri', serif
        }

        .pattern {
            background-color: #073b32;
            background-image: radial-gradient(#c99b4a33 1px, transparent 1px);
            background-size: 20px 20px
        }
    </style>
</head>

<body class="min-h-screen bg-[#f8f5ee] text-slate-800">
    <div class="flex min-h-screen">
        <section class="pattern hidden w-1/2 flex-col justify-between p-12 text-white lg:flex">
            <div>
                <div class="flex items-center gap-3">
                    <div class="grid h-12 w-12 place-items-center rounded-2xl bg-[#c99b4a] text-emerald-950"><i
                            class="bx bx-book-open text-2xl"></i></div><span
                        class="text-3xl font-extrabold">المحفظ</span>
                </div>
            </div>
            <div>
                <div class="mb-6 text-7xl text-[#c99b4a]"><i class="bx bx-book-bookmark"></i></div>
                <h1 class="max-w-lg text-4xl font-extrabold leading-relaxed">وبالقرآن نحيا، وبالحفظ نرتقي</h1>
                <p class="mt-5 text-lg text-emerald-100/60">منصة بسيطة لمتابعة طلابك في رحلتهم مع كتاب الله.</p>
            </div>
            <div class="text-sm text-emerald-100/40">© {{ date('Y') }} المحفظ</div>
        </section>
        <main class="flex w-full items-center justify-center p-6 sm:p-10 lg:w-1/2">
            <div class="w-full max-w-md">
                <div class="mb-10 text-center lg:text-right">
                    <div
                        class="mx-auto mb-6 grid h-16 w-16 place-items-center rounded-3xl bg-emerald-950 text-3xl text-[#c99b4a] shadow-lg lg:mx-0">
                        <i class="bx bx-book-open"></i></div>
                    <h2 class="text-3xl font-extrabold text-emerald-950">مرحبًا بك من جديد</h2>
                    <p class="mt-2 text-slate-500">سجّل الدخول لإدارة حلقة التحفيظ</p>
                </div>
                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">@csrf
                    <div><label for="email" class="mb-2 block text-sm font-bold">البريد الإلكتروني</label><input
                            id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10"
                            placeholder="admin@example.com">@error('email')<p class="mt-2 text-sm text-red-600">
                            {{ $message }}</p>@enderror</div>
                    <div><label for="password" class="mb-2 block text-sm font-bold">كلمة المرور</label><input
                            id="password" name="password" type="password" required
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10"
                            placeholder="••••••••">@error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}
                            </p>@enderror</div>
                    <label class="flex items-center gap-2 text-sm text-slate-500"><input type="checkbox" name="remember"
                            class="rounded border-slate-300 text-emerald-800"> تذكرني</label>
                    <button
                        class="flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-950 py-4 font-bold text-white shadow-lg shadow-emerald-950/20 transition hover:bg-emerald-800">دخول
                        إلى لوحة المحفظ <i class="bx bx-left-arrow-alt text-xl text-[#c99b4a]"></i></button>
                </form>
                <p class="mt-8 text-center text-xs text-slate-400">طريقك إلى الإتقان يبدأ بخطوة</p>
            </div>
        </main>
    </div>
</body>

</html>