<?php

namespace App\Http\Controllers;

use App\Models\AyahConfirmation;
use App\Models\Narration;
use App\Models\Qiraat;
use App\Models\RecitationSession;
use App\Models\Student;
use App\Services\AnalyticsReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function sendReport(AnalyticsReportService $reports): RedirectResponse
    {
        try { $reports->send(); return back()->with('success', 'تم إرسال تقرير PDF وملفات CSV إلى Telegram.'); }
        catch (\Throwable $exception) { report($exception); return back()->with('error', $exception->getMessage()); }
    }

    public function index(): View
    {
        $qiraats = Qiraat::with('narrations')->orderBy('id')->get();
        $narrations = Narration::with('qiraat')->orderBy('qiraat_id')->orderBy('sort_order')->get();
        $students = Student::with('progress.narration.qiraat')->withCount('confirmations')->orderBy('name')->get();
        $students->each(function (Student $student) use ($qiraats, $narrations): void {
            $student->narration_progress = $this->narrationProgress($narrations, $student->progress);
            $student->qiraat_progress = $this->qiraatProgress($qiraats, $student->narration_progress);
        });

        return view('analytics.index', [
            'studentCount' => $students->count(),
            'sessionCount' => RecitationSession::count(),
            'ayahCount' => AyahConfirmation::count(),
            'lastActivity' => AyahConfirmation::latest('confirmed_at')->first(),
            'students' => $students,
            'qiraats' => $qiraats,
            'narrations' => $narrations,
        ]);
    }

    public function student(Student $student): View
    {
        $student->load('progress.narration.qiraat');
        $qiraats = Qiraat::with('narrations')->orderBy('id')->get();
        $narrations = Narration::with('qiraat')->orderBy('qiraat_id')->orderBy('sort_order')->get();
        $narrationProgress = $this->narrationProgress($narrations, $student->progress);
        $qiraatProgress = $this->qiraatProgress($qiraats, $narrationProgress);
        $sessions = RecitationSession::where('student_id', $student->id)->count();
        $ayahCount = AyahConfirmation::where('student_id', $student->id)->count();
        $savedAyahsAcrossReadings = $student->progress->sum('global_ayah_number');
        $lastActivity = AyahConfirmation::where('student_id', $student->id)->latest('confirmed_at')->first();
        $activity = AyahConfirmation::with('narration.qiraat')
            ->where('student_id', $student->id)
            ->orderBy('confirmed_at')
            ->get()
            ->groupBy('recitation_session_id')
            ->map(function (Collection $confirmations) {
                $ordered = $confirmations->sortBy('global_ayah_number')->values();
                $narration = $confirmations->first()->narration;
                return (object) [
                    'start' => $ordered->first(),
                    'end' => $ordered->last(),
                    'qiraat_name' => $narration?->qiraat?->name,
                    'narration_name' => $narration?->name,
                    'confirmed_at' => $confirmations->max('confirmed_at'),
                    'ayah_count' => $confirmations->count(),
                ];
            })
            ->sortByDesc('confirmed_at')
            ->take(10)
            ->values();

        return view('analytics.student', compact('student', 'sessions', 'ayahCount', 'savedAyahsAcrossReadings', 'lastActivity', 'qiraatProgress', 'narrationProgress', 'activity'));
    }

    private function narrationProgress(Collection $narrations, Collection $progresses): Collection
    {
        return $narrations->map(function (Narration $narration) use ($progresses) {
            $progress = $progresses->firstWhere('narration_id', $narration->id);
            $savedAyahs = (int) ($progress?->global_ayah_number ?? 0);
            return (object) [
                'id' => $narration->id,
                'name' => $narration->name,
                'qiraat_id' => $narration->qiraat_id,
                'qiraat_name' => $narration->qiraat->name,
                'saved_ayahs' => $savedAyahs,
                'percentage' => round(min(100, ($savedAyahs / 6236) * 100), 2),
                'is_complete' => $progress?->is_complete ?? false,
                'progress' => $progress,
            ];
        });
    }

    private function qiraatProgress(Collection $qiraats, Collection $narrationProgress): Collection
    {
        return $qiraats->map(function (Qiraat $qiraat) use ($narrationProgress) {
            $narrations = $narrationProgress->where('qiraat_id', $qiraat->id)->values();
            return (object) [
                'id' => $qiraat->id,
                'name' => $qiraat->name,
                'imam' => $qiraat->imam,
                'narrations' => $narrations,
                'has_progress' => $narrations->contains(fn (object $item) => $item->saved_ayahs > 0),
                'is_complete' => $narrations->isNotEmpty() && $narrations->every(fn (object $item) => $item->is_complete),
            ];
        });
    }
}
