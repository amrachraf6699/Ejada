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
        return view('analytics.index', ['studentCount' => Student::count(), 'sessionCount' => RecitationSession::count(), 'ayahCount' => AyahConfirmation::count(), 'lastActivity' => AyahConfirmation::latest('confirmed_at')->first(), 'students' => Student::withCount('confirmations')->orderByDesc('confirmations_count')->get()]);
    }

    public function student(Student $student): View
    {
        $student->load(['progress.qiraat']);
        $sessions = RecitationSession::where('student_id', $student->id)->count();
        $ayahCount = AyahConfirmation::where('student_id', $student->id)->count();
        $activity = AyahConfirmation::where('student_id', $student->id)->latest('confirmed_at')->take(10)->get();
        return view('analytics.student', compact('student', 'sessions', 'ayahCount', 'activity'));
    }
}
