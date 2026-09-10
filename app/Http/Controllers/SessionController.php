<?php

namespace App\Http\Controllers;

use App\Exceptions\QuranApiException;
use App\Models\AyahConfirmation;
use App\Models\MemorizationProgress;
use App\Models\Narration;
use App\Models\Qiraat;
use App\Models\RecitationAttempt;
use App\Models\RecitationSession;
use App\Models\Student;
use App\Services\QuranContentService;
use App\Services\RecitationAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function students(Request $request): View
    {
        $students = Student::withCount(['attempts as completed_attempts_count' => fn ($q) => $q->whereNotNull('completed_at')])->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->string('search') . '%'))->orderBy('name')->get();
        return view('session.students', compact('students'));
    }

    public function readings(Student $student): View
    {
        $qiraats = Qiraat::with('narrations')->orderBy('id')->get();
        $attempts = RecitationAttempt::with('progress')->where('student_id', $student->id)->get()->groupBy('narration_id');
        return view('session.readings', compact('student', 'qiraats', 'attempts'));
    }

    public function narrations(Student $student, Qiraat $qiraat): View
    {
        $qiraat->load('narrations');
        $attempts = RecitationAttempt::with('progress')->where('student_id', $student->id)->whereIn('narration_id', $qiraat->narrations->pluck('id'))->get()->groupBy('narration_id');
        return view('session.narrations', compact('student', 'qiraat', 'attempts'));
    }

    public function mushaf(Student $student, Narration $narration, QuranContentService $quran, Request $request, RecitationAttemptService $attempts): View
    {
        $attempt = $attempts->active($student, $narration);
        $session = RecitationSession::firstOrCreate(['user_id' => $request->user()->id, 'recitation_attempt_id' => $attempt->id, 'ended_at' => null], ['student_id' => $student->id, 'narration_id' => $narration->id, 'started_at' => now()]);
        return $this->renderMushaf($student, $narration->qiraat, $narration, $attempt, $session, $quran, $request);
    }

    public function mushafBoth(Student $student, Qiraat $qiraat, QuranContentService $quran, Request $request, RecitationAttemptService $attempts): View|RedirectResponse
    {
        $narrations = $qiraat->narrations()->orderBy('sort_order')->get(); abort_unless($narrations->count() === 2, 404);
        $active = $narrations->map(fn ($n) => $attempts->active($student, $n));
        if ($active->pluck('attempt_number')->unique()->count() !== 1 || $active->pluck('progress.global_ayah_number')->unique()->count() !== 1) return back()->withErrors(['paired_readings' => 'لا يمكن التسميع للروايتين معًا لأن رقم المرة أو التقدم مختلف.']);
        $sessions = $active->map(fn ($attempt) => RecitationSession::firstOrCreate(['user_id' => $request->user()->id, 'recitation_attempt_id' => $attempt->id, 'ended_at' => null], ['student_id' => $student->id, 'narration_id' => $attempt->narration_id, 'started_at' => now()]));
        return $this->renderMushaf($student, $qiraat, $narrations->first(), $active->first(), $sessions->first(), $quran, $request, true);
    }

    private function renderMushaf(Student $student, Qiraat $qiraat, Narration $narration, RecitationAttempt $attempt, RecitationSession $session, QuranContentService $quran, Request $request, bool $isPairedSession = false): View
    {
        $progress = $attempt->loadMissing('progress')->progress; $page = min(604, max(1, (int) $request->integer('page', $progress->page_number ?: 1))); $mushaf = ['ayahs' => []]; $quranError = null;
        try { $mushaf = $quran->page($page); } catch (QuranApiException $e) { $quranError = $e->getMessage(); }
        $verses = $mushaf['ayahs'] ?? []; $surahs = collect(config('quran.surah_names'))->map(fn ($name, $number) => ['number' => $number, 'name' => $name, 'page' => config("quran.surah_start_pages.{$number}")])->values();
        $currentSurahNumber = $surahs->filter(fn ($s) => $s['page'] <= $page)->last()['number'] ?? 1;
        return view('session.mushaf', compact('student', 'qiraat', 'narration', 'attempt', 'progress', 'session', 'verses', 'page', 'quranError', 'surahs', 'currentSurahNumber', 'isPairedSession'));
    }

    public function page(Student $student, Narration $narration, int $page, QuranContentService $quran): JsonResponse
    { abort_unless($page >= 1 && $page <= 604, 404); try { return response()->json($quran->page($page)); } catch (QuranApiException $e) { return response()->json(['message' => $e->getMessage()], 503); } }

    public function confirm(Request $request, RecitationSession $session): RedirectResponse
    {
        abort_unless($session->user_id === $request->user()->id && ! $session->ended_at, 403); $data = $this->data($request); $completed = DB::transaction(fn () => $this->saveProgress($session, $data));
        if ($completed) { $session->update(['ended_at' => now()]); return redirect()->route('session.narrations', [$session->student_id, $session->narration->qiraat])->with('narration_completed', ['narration' => $session->narration->name, 'qiraat' => $session->narration->qiraat->name, 'attempt_number' => $session->attempt->attempt_number]); }
        return redirect()->route('dashboard')->with('success', 'تم حفظ تقدم التسميع بنجاح.');
    }

    public function confirmBoth(Request $request, Student $student, Qiraat $qiraat): RedirectResponse
    {
        $data = $this->data($request); $narrations = $qiraat->narrations()->orderBy('sort_order')->get(); abort_unless($narrations->count() === 2, 404);
        $sessions = $narrations->map(fn ($n) => RecitationSession::where('user_id', $request->user()->id)->where('student_id', $student->id)->where('narration_id', $n->id)->whereNull('ended_at')->latest()->firstOrFail());
        DB::transaction(function () use ($sessions, $data) { $attempts = $sessions->map(fn ($s) => $s->attempt->fresh()->load('progress')); if ($attempts->pluck('attempt_number')->unique()->count() !== 1 || $attempts->pluck('progress.global_ayah_number')->unique()->count() !== 1) throw ValidationException::withMessages(['paired_readings' => 'لا يمكن حفظ التقدم للروايتين لأن رقم المرة أو التقدم مختلف.']); foreach ($sessions as $s) $this->saveProgress($s, $data); });
        if ($data['global_ayah_number'] >= MemorizationProgress::TOTAL_AYAHS) { $sessions->each->update(['ended_at' => now()]); return redirect()->route('session.narrations', [$student, $qiraat])->with('narration_completed', ['narration' => 'كلا الروايتين', 'qiraat' => $qiraat->name, 'attempt_number' => $sessions->first()->attempt->attempt_number]); }
        return redirect()->route('dashboard')->with('success', 'تم حفظ تقدم الروايتين بنجاح.');
    }

    private function data(Request $request): array { return $request->validate(['surah_number' => ['required','integer','min:1'], 'ayah_number' => ['required','integer','min:1'], 'global_ayah_number' => ['required','integer','between:1,' . MemorizationProgress::TOTAL_AYAHS], 'page_number' => ['required','integer','between:1,604']]); }
    private function saveProgress(RecitationSession $session, array $data): bool
    {
        $attempt = RecitationAttempt::whereKey($session->recitation_attempt_id)->lockForUpdate()->firstOrFail(); $progress = MemorizationProgress::where('recitation_attempt_id', $attempt->id)->lockForUpdate()->firstOrFail(); $start = $progress->global_ayah_number + 1; $end = $data['global_ayah_number']; $range = $end >= $start ? range($start, $end) : [$end];
        foreach ($range as $number) { $location = $number === $end ? ['surah_number' => $data['surah_number'], 'ayah_number' => $data['ayah_number']] : app(QuranContentService::class)->locationForGlobalAyah($number); AyahConfirmation::updateOrCreate(['recitation_attempt_id' => $attempt->id, 'global_ayah_number' => $number], ['recitation_session_id' => $session->id, 'student_id' => $session->student_id, 'narration_id' => $session->narration_id, ...$location, 'confirmed_at' => now()]); }
        $progress->update([...$data, 'last_confirmed_at' => now()]); if ($end >= MemorizationProgress::TOTAL_AYAHS) { $attempt->update(['completed_at' => now()]); return true; } return false;
    }
    public function finish(Request $request, RecitationSession $session): RedirectResponse { abort_unless($session->user_id === $request->user()->id, 403); $session->update(['ended_at' => now()]); return redirect()->route('dashboard')->with('success', 'تم إنهاء جلسة التسميع.'); }
    public function finishBoth(Request $request, Student $student, Qiraat $qiraat): RedirectResponse { RecitationSession::where('user_id', $request->user()->id)->where('student_id', $student->id)->whereIn('narration_id', $qiraat->narrations()->pluck('id'))->whereNull('ended_at')->update(['ended_at' => now()]); return redirect()->route('dashboard')->with('success', 'تم إنهاء جلسة التسميع للروايتين.'); }
}
