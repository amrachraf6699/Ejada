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
            ['name' => 'نافع المدني', 'narrations' => ['قالون', 'ورش']],
            ['name' => 'ابن كثير المكي', 'narrations' => ['البزي', 'قنبل']],
            ['name' => 'أبو عمرو البصري', 'narrations' => ['الدوري', 'السوسي']],
            ['name' => 'ابن عامر الشامي', 'narrations' => ['هشام', 'ابن ذاكون']],
            ['name' => 'عاصم الكوفي', 'narrations' => ['شعبة', 'حفص']],
            ['name' => 'حمزة الكوفي', 'narrations' => ['خلف', 'خلاد']],
            ['name' => 'الكسائي', 'narrations' => ['أبو الحارث', 'الدوري']],
            ['name' => 'أبو جعفر المدني', 'narrations' => ['ابن وردان', 'ابن جماز']],
            ['name' => 'يعقوب الخضرمي', 'narrations' => ['رويس', 'روح']],
            ['name' => 'خلف العاشر', 'narrations' => ['إسحاق', 'إدريس']],
        ];

        foreach ($qiraats as $entry) {
            $qiraat = \App\Models\Qiraat::create([
                'name' => $entry['name'],
                'imam' => $entry['name'],
                'api_identifier' => 'quran-uthmani',
                'is_available' => true,
            ]);
            foreach ($entry['narrations'] as $index => $name) {
                $qiraat->narrations()->create(['name' => $name, 'sort_order' => $index + 1]);
            }
        }
    }
}
