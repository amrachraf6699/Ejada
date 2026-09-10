<?php

namespace Tests\Feature;

use App\Models\AyahConfirmation;
use App\Models\Narration;
use App\Models\Qiraat;
use App\Models\RecitationAttempt;
use App\Models\RecitationSession;
use App\Models\Student;
use App\Models\User;
use App\Services\RecitationAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecitationAttemptFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_narration_starts_a_new_attempt_and_reuses_ayahs(): void
    {
        $user = User::factory()->create();
        $student = Student::create(['name' => 'طالب المحاولات']);
        $qiraat = Qiraat::create(['name' => 'قراءة اختبار', 'imam' => 'إمام اختبار']);
        $narration = $qiraat->narrations()->create(['name' => 'رواية اختبار']);
        $service = app(RecitationAttemptService::class);
        $first = $service->active($student, $narration);
        $session = RecitationSession::create(['user_id' => $user->id, 'student_id' => $student->id, 'narration_id' => $narration->id, 'recitation_attempt_id' => $first->id, 'started_at' => now()]);

        $this->actingAs($user)->post(route('session.confirm', $session), ['surah_number' => 114, 'ayah_number' => 6, 'global_ayah_number' => 6236, 'page_number' => 604])->assertRedirect();
        $this->assertNotNull($first->fresh()->completed_at);

        $second = $service->active($student, $narration);
        $this->assertSame(2, $second->attempt_number);
        $this->assertSame(0, $second->progress->global_ayah_number);
        AyahConfirmation::create(['recitation_attempt_id' => $second->id, 'recitation_session_id' => $session->id, 'student_id' => $student->id, 'narration_id' => $narration->id, 'surah_number' => 1, 'ayah_number' => 1, 'global_ayah_number' => 1, 'confirmed_at' => now()]);
        $this->assertDatabaseCount('recitation_attempts', 2);
    }
}
