<?php

namespace Tests\Feature;

use App\Models\MemorizationProgress;
use App\Models\Narration;
use App\Models\Qiraat;
use App\Models\RecitationSession;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirming_next_ayah_updates_progress_for_one_narration(): void
    {
        [$qiraat, $first] = $this->narrationPair();
        $user = User::factory()->create();
        $student = Student::create(['name' => 'طالب الاختبار']);
        $session = RecitationSession::create(['user_id' => $user->id, 'student_id' => $student->id, 'narration_id' => $first->id, 'started_at' => now()]);

        $this->actingAs($user)->post(route('session.confirm', $session), ['surah_number' => 1, 'ayah_number' => 1, 'global_ayah_number' => 1, 'page_number' => 1])->assertRedirect();

        $this->assertDatabaseHas('memorization_progress', ['student_id' => $student->id, 'narration_id' => $first->id, 'global_ayah_number' => 1]);
        $this->assertDatabaseHas('ayah_confirmations', ['student_id' => $student->id, 'narration_id' => $first->id, 'global_ayah_number' => 1]);
    }

    public function test_confirming_an_ayah_records_the_range_for_its_narration(): void
    {
        [, $first] = $this->narrationPair();
        $user = User::factory()->create();
        $student = Student::create(['name' => 'طالب النطاق']);
        $session = RecitationSession::create(['user_id' => $user->id, 'student_id' => $student->id, 'narration_id' => $first->id, 'started_at' => now()]);
        MemorizationProgress::create(['student_id' => $student->id, 'narration_id' => $first->id, 'surah_number' => 1, 'ayah_number' => 2, 'global_ayah_number' => 2]);

        $this->actingAs($user)->post(route('session.confirm', $session), ['surah_number' => 1, 'ayah_number' => 5, 'global_ayah_number' => 5, 'page_number' => 1])->assertRedirect();

        $this->assertDatabaseCount('ayah_confirmations', 3);
        $this->assertDatabaseHas('ayah_confirmations', ['narration_id' => $first->id, 'global_ayah_number' => 3]);
        $this->assertDatabaseHas('ayah_confirmations', ['narration_id' => $first->id, 'global_ayah_number' => 5]);
    }

    public function test_progress_is_independent_between_narrations_of_the_same_qiraat(): void
    {
        [, $first, $second] = $this->narrationPair();
        $student = Student::create(['name' => 'طالب الاختبار']);
        MemorizationProgress::create(['student_id' => $student->id, 'narration_id' => $first->id, 'global_ayah_number' => 7, 'surah_number' => 1, 'ayah_number' => 7]);

        $this->assertSame(7, MemorizationProgress::where('narration_id', $first->id)->value('global_ayah_number'));
        $this->assertNull(MemorizationProgress::where('narration_id', $second->id)->value('global_ayah_number'));
    }

    public function test_qiraat_then_narration_routes_are_available(): void
    {
        [$qiraat] = $this->narrationPair();
        $user = User::factory()->create();
        $student = Student::create(['name' => 'طالب المسارات']);

        $this->actingAs($user)->get(route('session.readings', $student))->assertOk()->assertSee($qiraat->name);
        $this->actingAs($user)->get(route('session.narrations', [$student, $qiraat]))->assertOk()->assertSee('رواية الاختبار الأولى')->assertSee('رواية الاختبار الثانية');
    }

    public function test_completing_both_narrations_completes_the_parent_qiraat(): void
    {
        [$qiraat, $first, $second] = $this->narrationPair();
        $user = User::factory()->create();
        $student = Student::create(['name' => 'طالب مكتمل']);
        MemorizationProgress::create(['student_id' => $student->id, 'narration_id' => $first->id, 'surah_number' => 114, 'ayah_number' => 6, 'global_ayah_number' => 6236, 'page_number' => 604]);
        MemorizationProgress::create(['student_id' => $student->id, 'narration_id' => $second->id, 'surah_number' => 114, 'ayah_number' => 5, 'global_ayah_number' => 6235, 'page_number' => 604]);
        $session = RecitationSession::create(['user_id' => $user->id, 'student_id' => $student->id, 'narration_id' => $second->id, 'started_at' => now()]);

        $this->actingAs($user)->post(route('session.confirm', $session), ['surah_number' => 114, 'ayah_number' => 6, 'global_ayah_number' => 6236, 'page_number' => 604])
            ->assertRedirect(route('session.narrations', [$student, $qiraat]))
            ->assertSessionHas('narration_completed', fn (array $completion) => $completion['narration'] === $second->name && $completion['qiraat_complete']);

        $this->assertNotNull($session->fresh()->ended_at);
    }

    public function test_completing_one_narration_does_not_complete_the_parent_qiraat(): void
    {
        [$qiraat, $first] = $this->narrationPair();
        $user = User::factory()->create();
        $student = Student::create(['name' => 'طالب رواية واحدة']);
        MemorizationProgress::create(['student_id' => $student->id, 'narration_id' => $first->id, 'surah_number' => 114, 'ayah_number' => 5, 'global_ayah_number' => 6235, 'page_number' => 604]);
        $session = RecitationSession::create(['user_id' => $user->id, 'student_id' => $student->id, 'narration_id' => $first->id, 'started_at' => now()]);

        $this->actingAs($user)->post(route('session.confirm', $session), ['surah_number' => 114, 'ayah_number' => 6, 'global_ayah_number' => 6236, 'page_number' => 604])
            ->assertRedirect(route('session.narrations', [$student, $qiraat]))
            ->assertSessionHas('narration_completed', fn (array $completion) => ! $completion['qiraat_complete']);
    }

    public function test_analytics_exposes_qiraat_and_narration_views(): void
    {
        [$qiraat] = $this->narrationPair();
        $user = User::factory()->create();
        $student = Student::create(['name' => 'طالب التحليلات']);

        $this->actingAs($user)->get(route('analytics'))->assertOk()->assertSee('القراءات')->assertSee('الروايات')->assertSee($qiraat->name);
        $this->actingAs($user)->get(route('analytics.student', $student))->assertOk()->assertSee('القراءات')->assertSee('الروايات');
    }

    public function test_seeder_creates_the_ten_qiraats_and_twenty_narrations(): void
    {
        app(DatabaseSeeder::class)->run();

        $this->assertDatabaseCount('qiraats', 10);
        $this->assertDatabaseCount('narrations', 20);
        $expected = [
            'نافع المدني' => ['قالون', 'ورش'], 'ابن كثير المكي' => ['البزي', 'قنبل'], 'أبو عمرو البصري' => ['الدوري', 'السوسي'],
            'ابن عامر الشامي' => ['هشام', 'ابن ذاكون'], 'عاصم الكوفي' => ['شعبة', 'حفص'], 'حمزة الكوفي' => ['خلف', 'خلاد'],
            'الكسائي' => ['أبو الحارث', 'الدوري'], 'أبو جعفر المدني' => ['ابن وردان', 'ابن جماز'], 'يعقوب الخضرمي' => ['رويس', 'روح'], 'خلف العاشر' => ['إسحاق', 'إدريس'],
        ];

        foreach ($expected as $imam => $narrations) {
            $qiraat = Qiraat::where('imam', $imam)->firstOrFail();
            $this->assertSame($imam, $qiraat->name);
            $this->assertSame($narrations, $qiraat->narrations->pluck('name')->all());
        }
    }

    private function narrationPair(): array
    {
        $qiraat = Qiraat::create(['name' => 'قراءة الاختبار', 'imam' => 'إمام الاختبار', 'is_available' => true]);
        $first = $qiraat->narrations()->create(['name' => 'رواية الاختبار الأولى', 'sort_order' => 1]);
        $second = $qiraat->narrations()->create(['name' => 'رواية الاختبار الثانية', 'sort_order' => 2]);
        return [$qiraat, $first, $second];
    }
}
