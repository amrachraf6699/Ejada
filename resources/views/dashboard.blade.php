@extends('layouts.app', ['title' => 'بدء جلسة'])
@section('content')
    <div class="flex min-h-[calc(100vh-10rem)] flex-col items-center justify-center text-center">
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
@endsection