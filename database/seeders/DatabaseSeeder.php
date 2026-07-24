<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(['email' => 'admin@example.com'], ['name' => 'شيخ سعيد شاهين', 'password' => 'password']);
        if (\App\Models\Student::count() === 0) {
            \App\Models\Student::insert([
                ['name' => 'عبد الرحمن محمد', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'يوسف أحمد', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'عمر خالد', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'سعد إبراهيم', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'معاذ حسن', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        $qiraats = [
            ['name' => 'قراءة نافع المدني', 'imam' => 'نافع المدني', 'api_identifier' => env('QURAN_QIRAAT_NAFI'), 'is_available' => true],
            ['name' => 'قراءة ابن كثير المكي', 'imam' => 'ابن كثير المكي', 'api_identifier' => env('QURAN_QIRAAT_IBN_KATHIR'), 'is_available' => true],
            ['name' => 'قراءة أبي عمرو البصري', 'imam' => 'أبو عمرو البصري', 'api_identifier' => env('QURAN_QIRAAT_ABU_AMR'), 'is_available' => true],
            ['name' => 'قراءة ابن عامر الشامي', 'imam' => 'ابن عامر الشامي', 'api_identifier' => env('QURAN_QIRAAT_IBN_AMER'), 'is_available' => true],
            ['name' => 'قراءة عاصم الكوفي', 'imam' => 'عاصم الكوفي', 'api_identifier' => env('QURAN_QIRAAT_ASIM'), 'is_available' => true],
            ['name' => 'قراءة حمزة الكوفي', 'imam' => 'حمزة الكوفي', 'api_identifier' => env('QURAN_QIRAAT_HAMZA'), 'is_available' => true],
            ['name' => 'قراءة الكسائي الكوفي', 'imam' => 'الكسائي الكوفي', 'api_identifier' => env('QURAN_QIRAAT_KISAI'), 'is_available' => true],
            ['name' => 'قراءة أبي جعفر المدني', 'imam' => 'أبو جعفر المدني', 'api_identifier' => env('QURAN_QIRAAT_ABU_JAFAR'), 'is_available' => true],
            ['name' => 'قراءة يعقوب الحضرمي', 'imam' => 'يعقوب الحضرمي', 'api_identifier' => env('QURAN_QIRAAT_YAQUB'), 'is_available' => true],
            ['name' => 'قراءة خلف العاشر', 'imam' => 'خلف العاشر', 'api_identifier' => env('QURAN_QIRAAT_KHALAF'), 'is_available' => true],
        ];

        foreach ($qiraats as $qiraat) {
            \App\Models\Qiraat::updateOrCreate(['imam' => $qiraat['imam']], $qiraat);
        }
        \App\Models\Qiraat::query()->update(['api_identifier' => 'quran-uthmani', 'is_available' => true]);
    }
}
