<?php

namespace Tests\Feature;

use App\Models\MemorizationProgress;
use App\Models\Qiraat;
use App\Models\RecitationSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirming_next_ayah_updates_progress_for_one_qiraat(): void
    {
        $user = User::factory()->create();
        $student = Student::create(['name' => 'طالب الاختبار']);
        $qiraat = Qiraat::create(['name' => 'قراءة الاختبار', 'imam' => 'إمام الاختبار', 'is_available' => true]);
        $session = RecitationSession::create(['user_id' => $user->id, 'student_id' => $student->id, 'qiraat_id' => $qiraat->id, 'started_at' => now()]);

        $response = $this->actingAs($user)->post(route('session.confirm', $session), ['surah_number' => 1, 'ayah_number' => 1, 'global_ayah_number' => 1, 'page_number' => 1]);

        $response->assertRedirect();
        $this->assertDatabaseHas('memorization_progress', ['student_id' => $student->id, 'qiraat_id' => $qiraat->id, 'global_ayah_number' => 1]);
        $this->assertDatabaseHas('ayah_confirmations', ['student_id' => $student->id, 'qiraat_id' => $qiraat->id, 'global_ayah_number' => 1]);
    }

    public function test_any_ayah_can_be_confirmed(): void
    {
        $user = User::factory()->create();
        $student = Student::create(['name' => 'طالب الاختبار']);
        $qiraat = Qiraat::create(['name' => 'قراءة الاختبار', 'imam' => 'إمام الاختبار', 'is_available' => true]);
        $session = RecitationSession::create(['user_id' => $user->id, 'student_id' => $student->id, 'qiraat_id' => $qiraat->id, 'started_at' => now()]);
        MemorizationProgress::create(['student_id' => $student->id, 'qiraat_id' => $qiraat->id, 'surah_number' => 1, 'ayah_number' => 1, 'global_ayah_number' => 1]);

        $this->actingAs($user)->post(route('session.confirm', $session), ['surah_number' => 1, 'ayah_number' => 3, 'global_ayah_number' => 3, 'page_number' => 1])->assertRedirect();
        $this->assertDatabaseHas('ayah_confirmations', ['student_id' => $student->id, 'global_ayah_number' => 3]);
        $this->assertDatabaseHas('memorization_progress', ['student_id' => $student->id, 'qiraat_id' => $qiraat->id, 'global_ayah_number' => 3]);
        $this->assertDatabaseCount('ayah_confirmations', 2);
    }

    public function test_confirming_an_ayah_records_the_whole_range_from_previous_progress(): void
    {
        $user = User::factory()->create();
        $student = Student::create(['name' => 'طالب النطاق']);
        $qiraat = Qiraat::create(['name' => 'قراءة الاختبار', 'imam' => 'إمام الاختبار', 'is_available' => true]);
        $session = RecitationSession::create(['user_id' => $user->id, 'student_id' => $student->id, 'qiraat_id' => $qiraat->id, 'started_at' => now()]);
        MemorizationProgress::create(['student_id' => $student->id, 'qiraat_id' => $qiraat->id, 'surah_number' => 1, 'ayah_number' => 2, 'global_ayah_number' => 2]);
        $this->actingAs($user)->post(route('session.confirm', $session), ['surah_number' => 1, 'ayah_number' => 5, 'global_ayah_number' => 5, 'page_number' => 1])->assertRedirect();
        $this->assertDatabaseCount('ayah_confirmations', 3);
        $this->assertDatabaseHas('ayah_confirmations', ['student_id' => $student->id, 'global_ayah_number' => 3]);
        $this->assertDatabaseHas('ayah_confirmations', ['student_id' => $student->id, 'global_ayah_number' => 4]);
        $this->assertDatabaseHas('ayah_confirmations', ['student_id' => $student->id, 'global_ayah_number' => 5]);
    }

    public function test_progress_is_independent_between_qiraats(): void
    {
        $student = Student::create(['name' => 'طالب الاختبار']);
        $first = Qiraat::create(['name' => 'الأولى', 'imam' => 'الأول', 'is_available' => true]);
        $second = Qiraat::create(['name' => 'الثانية', 'imam' => 'الثاني', 'is_available' => true]);
        MemorizationProgress::create(['student_id' => $student->id, 'qiraat_id' => $first->id, 'global_ayah_number' => 7, 'surah_number' => 1, 'ayah_number' => 7]);

        $this->assertSame(7, MemorizationProgress::where('qiraat_id', $first->id)->value('global_ayah_number'));
        $this->assertNull(MemorizationProgress::where('qiraat_id', $second->id)->value('global_ayah_number'));
    }
}
