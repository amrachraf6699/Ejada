<?php

namespace App\Http\Controllers;

use App\Exceptions\QuranApiException;
use App\Models\AyahConfirmation;
use App\Models\MemorizationProgress;
use App\Models\Qiraat;
use App\Models\RecitationSession;
use App\Models\Student;
use App\Services\QuranContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $qiraats = Qiraat::with(['progress' => fn ($query) => $query->where('student_id', $student->id)])->orderBy('id')->get();
        return view('session.readings', compact('student', 'qiraats'));
    }

    public function mushaf(Student $student, Qiraat $qiraat, QuranContentService $quran, Request $request): View
    {
        $progress = MemorizationProgress::firstOrCreate(['student_id' => $student->id, 'qiraat_id' => $qiraat->id], ['surah_number' => 1, 'ayah_number' => 0, 'global_ayah_number' => 0, 'page_number' => 1]);
        $session = RecitationSession::firstOrCreate(['user_id' => $request->user()->id, 'student_id' => $student->id, 'qiraat_id' => $qiraat->id, 'ended_at' => null], ['started_at' => now()]);
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

        return view('session.mushaf', compact('student', 'qiraat', 'progress', 'session', 'verses', 'page', 'quranError', 'surahs', 'currentSurahNumber'));
    }

    public function page(Student $student, Qiraat $qiraat, int $page, QuranContentService $quran): JsonResponse
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
            $progress = MemorizationProgress::firstOrCreate(['student_id' => $session->student_id, 'qiraat_id' => $session->qiraat_id], ['surah_number' => 1, 'ayah_number' => 0, 'global_ayah_number' => 0, 'page_number' => 1]);
            $progress = MemorizationProgress::whereKey($progress->id)->lockForUpdate()->first();
            $start = $progress->global_ayah_number + 1;
            $end = $data['global_ayah_number'];
            $range = $end >= $start ? range($start, $end) : [$end];
            foreach ($range as $globalAyahNumber) {
                $location = $globalAyahNumber === $end
                    ? ['surah_number' => $data['surah_number'], 'ayah_number' => $data['ayah_number']]
                    : app(QuranContentService::class)->locationForGlobalAyah($globalAyahNumber);
                AyahConfirmation::updateOrCreate(
                    ['student_id' => $session->student_id, 'qiraat_id' => $session->qiraat_id, 'global_ayah_number' => $globalAyahNumber],
                    ['recitation_session_id' => $session->id, 'surah_number' => $location['surah_number'], 'ayah_number' => $location['ayah_number'], 'confirmed_at' => now()]
                );
            }
            $progress->update(['surah_number' => $data['surah_number'], 'ayah_number' => $data['ayah_number'], 'global_ayah_number' => $data['global_ayah_number'], 'page_number' => $data['page_number'], 'last_confirmed_at' => now()]);
            return $progress->is_complete;
        });

        if ($isComplete) {
            $session->update(['ended_at' => now()]);
            return redirect()->route('session.readings', $session->student_id)->with('reading_completed', $session->qiraat->name);
        }

        return redirect()->route('dashboard')->with('success', 'تم حفظ تقدم التسميع بنجاح.');
    }

    public function finish(Request $request, RecitationSession $session): RedirectResponse
    {
        abort_unless($session->user_id === $request->user()->id, 403);
        $session->update(['ended_at' => now()]);
        return redirect()->route('dashboard')->with('success', 'تم إنهاء جلسة التسميع.');
    }
}
