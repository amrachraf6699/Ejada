<?php

namespace App\Services;

use App\Models\MemorizationProgress;
use App\Models\Narration;
use App\Models\RecitationAttempt;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class RecitationAttemptService
{
    public function active(Student $student, Narration $narration): RecitationAttempt
    {
        return DB::transaction(function () use ($student, $narration) {
            $attempt = RecitationAttempt::where('student_id', $student->id)->where('narration_id', $narration->id)->whereNull('completed_at')->latest('attempt_number')->lockForUpdate()->first();
            if ($attempt) return $attempt->load('progress');
            $number = (int) RecitationAttempt::where('student_id', $student->id)->where('narration_id', $narration->id)->max('attempt_number') + 1;
            $attempt = RecitationAttempt::create(['student_id' => $student->id, 'narration_id' => $narration->id, 'attempt_number' => $number, 'started_at' => now()]);
            MemorizationProgress::create(['student_id' => $student->id, 'narration_id' => $narration->id, 'recitation_attempt_id' => $attempt->id, 'surah_number' => 1, 'ayah_number' => 0, 'global_ayah_number' => 0, 'page_number' => 1]);
            return $attempt->load('progress');
        });
    }

    public function completedCount(int $studentId, int $narrationId): int
    {
        return RecitationAttempt::where('student_id', $studentId)->where('narration_id', $narrationId)->whereNotNull('completed_at')->count();
    }
}
