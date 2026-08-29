<?php

namespace App\Http\Controllers;

use App\Exceptions\QuranApiException;
use App\Models\AyahConfirmation;
use App\Models\MemorizationProgress;
use App\Models\Narration;
use App\Models\Qiraat;
use App\Models\RecitationSession;
use App\Models\Student;
use App\Services\QuranContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function students(Request $request): View
    {
        $students = Student::query()->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%' . $request->string('search') . '%'))->orderBy('name')->get();
        return view('session.students', compact('students'));
    }

    public function readings(Student $student): View
    {
        $qiraats = Qiraat::with(['narrations.progress' => fn ($query) => $query->where('student_id', $student->id)])->orderBy('id')->get();
        return view('session.readings', compact('student', 'qiraats'));
    }

    public function narrations(Student $student, Qiraat $qiraat): View
    {
        $qiraat->load(['narrations.progress' => fn ($query) => $query->where('student_id', $student->id)]);
        return view('session.narrations', compact('student', 'qiraat'));
    }

    public function mushaf(Student $student, Narration $narration, QuranContentService $quran, Request $request): View
    {
        $qiraat = $narration->qiraat;
        $progress = MemorizationProgress::firstOrCreate(['student_id' => $student->id, 'narration_id' => $narration->id], ['surah_number' => 1, 'ayah_number' => 0, 'global_ayah_number' => 0, 'page_number' => 1]);
        $session = RecitationSession::firstOrCreate(['user_id' => $request->user()->id, 'student_id' => $student->id, 'narration_id' => $narration->id, 'ended_at' => null], ['started_at' => now()]);
        return $this->renderMushaf($student, $qiraat, $narration, $progress, $session, $quran, $request);
    }

    public function mushafBoth(Student $student, Qiraat $qiraat, QuranContentService $quran, Request $request): View|RedirectResponse
    {
        $narrations = $qiraat->narrations()->orderBy('sort_order')->get();
        abort_unless($narrations->count() === 2, 404);

        $progress = $narrations->map(fn (Narration $narration) => MemorizationProgress::firstOrCreate(
            ['student_id' => $student->id, 'narration_id' => $narration->id],
            ['surah_number' => 1, 'ayah_number' => 0, 'global_ayah_number' => 0, 'page_number' => 1]
        ));
        if ($progress->pluck('global_ayah_number')->unique()->count() !== 1) {
            return redirect()->route('session.narrations', [$student, $qiraat])->withErrors(['paired_readings' => 'لا يمكن التسميع للروايتين معًا لأن تقدمهما مختلف.']);
        }

        $sessions = $narrations->map(fn (Narration $narration) => RecitationSession::firstOrCreate(
            ['user_id' => $request->user()->id, 'student_id' => $student->id, 'narration_id' => $narration->id, 'ended_at' => null],
            ['started_at' => now()]
        ));

        return $this->renderMushaf($student, $qiraat, $narrations->first(), $progress->first(), $sessions->first(), $quran, $request, true);
    }

    private function renderMushaf(Student $student, Qiraat $qiraat, Narration $narration, MemorizationProgress $progress, RecitationSession $session, QuranContentService $quran, Request $request, bool $isPairedSession = false): View
    {
        $page = min(604, max(1, (int) $request->integer('page', $progress->page_number ?: 1)));
        $mushaf = ['ayahs' => []];
        $quranError = null;
        try {
            $mushaf = $quran->page($page);
        } catch (QuranApiException $exception) {
            $quranError = $exception->getMessage();
        }
        $verses = $mushaf['ayahs'] ?? [];
        $surahs = collect(config('quran.surah_names'))->map(fn (string $name, int $number) => [
            'number' => $number,
            'name' => $name,
            'page' => config("quran.surah_start_pages.{$number}"),
        ])->values();
        $currentSurahNumber = $surahs
            ->filter(fn (array $surah) => $surah['page'] <= $page)
            ->last()['number'] ?? 1;

        return view('session.mushaf', compact('student', 'qiraat', 'narration', 'progress', 'session', 'verses', 'page', 'quranError', 'surahs', 'currentSurahNumber', 'isPairedSession'));
    }

    public function page(Student $student, Narration $narration, int $page, QuranContentService $quran): JsonResponse
    {
        abort_unless($page >= 1 && $page <= 604, 404);
        try {
            return response()->json($quran->page($page));
        } catch (QuranApiException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    public function confirm(Request $request, RecitationSession $session): RedirectResponse
    {
        abort_unless($session->user_id === $request->user()->id && ! $session->ended_at, 403);
        $data = $request->validate(['surah_number' => ['required', 'integer', 'min:1'], 'ayah_number' => ['required', 'integer', 'min:1'], 'global_ayah_number' => ['required', 'integer', 'between:1,' . MemorizationProgress::TOTAL_AYAHS], 'page_number' => ['required', 'integer', 'between:1,604']]);

        $isComplete = DB::transaction(function () use ($session, $data) {
            $progress = MemorizationProgress::firstOrCreate(['student_id' => $session->student_id, 'narration_id' => $session->narration_id], ['surah_number' => 1, 'ayah_number' => 0, 'global_ayah_number' => 0, 'page_number' => 1]);
            $progress = MemorizationProgress::whereKey($progress->id)->lockForUpdate()->first();
            $start = $progress->global_ayah_number + 1;
            $end = $data['global_ayah_number'];
            $range = $end >= $start ? range($start, $end) : [$end];
            foreach ($range as $globalAyahNumber) {
                $location = $globalAyahNumber === $end
                    ? ['surah_number' => $data['surah_number'], 'ayah_number' => $data['ayah_number']]
                    : app(QuranContentService::class)->locationForGlobalAyah($globalAyahNumber);
                AyahConfirmation::updateOrCreate(
                    ['student_id' => $session->student_id, 'narration_id' => $session->narration_id, 'global_ayah_number' => $globalAyahNumber],
                    ['recitation_session_id' => $session->id, 'surah_number' => $location['surah_number'], 'ayah_number' => $location['ayah_number'], 'confirmed_at' => now()]
                );
            }
            $progress->update(['surah_number' => $data['surah_number'], 'ayah_number' => $data['ayah_number'], 'global_ayah_number' => $data['global_ayah_number'], 'page_number' => $data['page_number'], 'last_confirmed_at' => now()]);
            return $progress->is_complete;
        });

        if ($isComplete) {
            $session->update(['ended_at' => now()]);
            $narration = $session->narration;
            $qiraat = $narration->qiraat;
            $qiraat->load(['narrations.progress' => fn ($query) => $query->where('student_id', $session->student_id)]);
            $qiraatComplete = $qiraat->narrations->every(fn (Narration $item) => $item->progress->first()?->is_complete);
            return redirect()->route('session.narrations', [$session->student_id, $qiraat])->with('narration_completed', [
                'narration' => $narration->name,
                'qiraat' => $qiraat->name,
                'qiraat_complete' => $qiraatComplete,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'تم حفظ تقدم التسميع بنجاح.');
    }

    public function confirmBoth(Request $request, Student $student, Qiraat $qiraat): RedirectResponse
    {
        $data = $request->validate(['surah_number' => ['required', 'integer', 'min:1'], 'ayah_number' => ['required', 'integer', 'min:1'], 'global_ayah_number' => ['required', 'integer', 'between:1,' . MemorizationProgress::TOTAL_AYAHS], 'page_number' => ['required', 'integer', 'between:1,604']]);
        $narrations = $qiraat->narrations()->orderBy('sort_order')->get();
        abort_unless($narrations->count() === 2, 404);

        $isComplete = DB::transaction(function () use ($request, $student, $narrations, $data) {
            $progresses = $narrations->map(function (Narration $narration) use ($student) {
                $progress = MemorizationProgress::firstOrCreate(['student_id' => $student->id, 'narration_id' => $narration->id], ['surah_number' => 1, 'ayah_number' => 0, 'global_ayah_number' => 0, 'page_number' => 1]);
                return MemorizationProgress::whereKey($progress->id)->lockForUpdate()->firstOrFail();
            });
            if ($progresses->pluck('global_ayah_number')->unique()->count() !== 1) {
                throw ValidationException::withMessages(['paired_readings' => 'لا يمكن حفظ التقدم للروايتين لأن تقدمهما مختلف.']);
            }

            foreach ($narrations as $index => $narration) {
                $progress = $progresses[$index];
                $session = RecitationSession::firstOrCreate(['user_id' => $request->user()->id, 'student_id' => $student->id, 'narration_id' => $narration->id, 'ended_at' => null], ['started_at' => now()]);
                $start = $progress->global_ayah_number + 1;
                $end = $data['global_ayah_number'];
                $range = $end >= $start ? range($start, $end) : [$end];
                foreach ($range as $globalAyahNumber) {
                    $location = $globalAyahNumber === $end ? ['surah_number' => $data['surah_number'], 'ayah_number' => $data['ayah_number']] : app(QuranContentService::class)->locationForGlobalAyah($globalAyahNumber);
                    AyahConfirmation::updateOrCreate(['student_id' => $student->id, 'narration_id' => $narration->id, 'global_ayah_number' => $globalAyahNumber], ['recitation_session_id' => $session->id, 'surah_number' => $location['surah_number'], 'ayah_number' => $location['ayah_number'], 'confirmed_at' => now()]);
                }
                $progress->update(['surah_number' => $data['surah_number'], 'ayah_number' => $data['ayah_number'], 'global_ayah_number' => $data['global_ayah_number'], 'page_number' => $data['page_number'], 'last_confirmed_at' => now()]);
                if ($progress->is_complete) $session->update(['ended_at' => now()]);
            }

            return $data['global_ayah_number'] >= MemorizationProgress::TOTAL_AYAHS;
        });

        return $isComplete
            ? redirect()->route('session.narrations', [$student, $qiraat])->with('narration_completed', ['narration' => 'كلا الروايتين', 'qiraat' => $qiraat->name, 'qiraat_complete' => true])
            : redirect()->route('dashboard')->with('success', 'تم حفظ تقدم الروايتين بنجاح.');
    }

    public function finish(Request $request, RecitationSession $session): RedirectResponse
    {
        abort_unless($session->user_id === $request->user()->id, 403);
        $session->update(['ended_at' => now()]);
        return redirect()->route('dashboard')->with('success', 'تم إنهاء جلسة التسميع.');
    }

    public function finishBoth(Request $request, Student $student, Qiraat $qiraat): RedirectResponse
    {
        RecitationSession::query()->where('user_id', $request->user()->id)->where('student_id', $student->id)->whereIn('narration_id', $qiraat->narrations()->pluck('id'))->whereNull('ended_at')->update(['ended_at' => now()]);
        return redirect()->route('dashboard')->with('success', 'تم إنهاء جلسة التسميع للروايتين.');
    }
}
