<?php

namespace App\Http\Controllers;

use App\Models\AyahConfirmation;
use App\Models\MemorizationProgress;
use App\Models\Qiraat;
use App\Models\RecitationSession;
use App\Models\Student;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $qiraats = Qiraat::orderBy('id')->get();
        $students = Student::with('progress')->withCount('confirmations')->orderBy('name')->get();
        $students->each(function (Student $student) use ($qiraats): void {
            $student->reading_progress = $this->readingProgress($qiraats, $student->progress);
        });

        return view('analytics.index', [
            'studentCount' => $students->count(),
            'sessionCount' => RecitationSession::count(),
            'ayahCount' => AyahConfirmation::count(),
            'lastActivity' => AyahConfirmation::latest('confirmed_at')->first(),
            'students' => $students,
            'qiraats' => $qiraats,
        ]);
    }

    public function student(Student $student): View
    {
        $student->load(['progress.qiraat']);
        $qiraats = Qiraat::orderBy('id')->get();
        $readingProgress = $this->readingProgress($qiraats, $student->progress);
        $sessions = RecitationSession::where('student_id', $student->id)->count();
        $ayahCount = AyahConfirmation::where('student_id', $student->id)->count();
        $savedAyahsAcrossReadings = $student->progress->sum('global_ayah_number');
        $lastActivity = AyahConfirmation::where('student_id', $student->id)->latest('confirmed_at')->first();
        $activity = AyahConfirmation::with('qiraat')
            ->where('student_id', $student->id)
            ->orderBy('confirmed_at')
            ->get()
            ->groupBy('recitation_session_id')
            ->map(function ($confirmations) {
                $ordered = $confirmations->sortBy('global_ayah_number')->values();
                return (object) [
                    'start' => $ordered->first(),
                    'end' => $ordered->last(),
                    'qiraat_name' => $confirmations->first()->qiraat?->name,
                    'confirmed_at' => $confirmations->max('confirmed_at'),
                    'ayah_count' => $confirmations->count(),
                ];
            })
            ->sortByDesc('confirmed_at')
            ->take(10)
            ->values();
        return view('analytics.student', compact('student', 'sessions', 'ayahCount', 'savedAyahsAcrossReadings', 'lastActivity', 'readingProgress', 'activity'));
    }

    private function readingProgress($qiraats, $progresses)
    {
        return $qiraats->map(function (Qiraat $qiraat) use ($progresses) {
            $progress = $progresses->firstWhere('qiraat_id', $qiraat->id);
            $savedAyahs = (int) ($progress?->global_ayah_number ?? 0);
            return (object) [
                'id' => $qiraat->id,
                'name' => $qiraat->name,
                'imam' => $qiraat->imam,
                'saved_ayahs' => $savedAyahs,
                'percentage' => round(min(100, ($savedAyahs / 6236) * 100), 2),
                'progress' => $progress,
            ];
        });
    }
}
