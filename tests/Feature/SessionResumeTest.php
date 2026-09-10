<?php

namespace Tests\Feature;

use App\Models\Qiraat;
use App\Models\Student;
use App\Models\User;
use App\Services\QuranContentService;
use App\Services\RecitationAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SessionResumeTest extends TestCase
{
    use RefreshDatabase;

    private function setupPair(bool $lookupFails = false): array
    {
        $this->actingAs(User::factory()->create());
        $student = Student::create(['name' => 'طالب الاختبار']);
        $qiraat = Qiraat::create(['name' => 'عاصم', 'imam' => 'عاصم']);
        $first = $qiraat->narrations()->create(['name' => 'شعبة', 'sort_order' => 1]);
        $second = $qiraat->narrations()->create(['name' => 'حفص', 'sort_order' => 2]);
        $service = app(RecitationAttemptService::class);
        $a = $service->active($student, $first);
        $completed = $service->active($student, $second);
        $completed->update(['completed_at' => now()]);
        $b = $service->active($student, $second);
        $global = app(QuranContentService::class)->globalAyahNumber(41, 54);
        foreach ([$a, $b] as $attempt) {
            $attempt->progress->update(['surah_number' => 41, 'ayah_number' => 54, 'global_ayah_number' => $global, 'page_number' => 477]);
        }
        Http::preventStrayRequests();
        Http::fake([
            '*/ayah/'.$global.'/quran-uthmani' => $lookupFails ? Http::response([], 503) : Http::response(['data' => ['page' => 482]]),
            '*/page/477/quran-uthmani' => Http::response(['data' => ['number' => 477, 'ayahs' => []]]),
            '*/page/482/quran-uthmani' => Http::response(['data' => ['number' => 482, 'ayahs' => [
                ['number' => $global, 'numberInSurah' => 54, 'surah' => ['number' => 41], 'page' => 482, 'text' => 'آية الاختبار'],
            ]]]),
        ]);
        return [$student, $qiraat, $first, $a, $b];
    }

    public function test_matching_ayahs_allow_different_attempt_numbers_and_save_independently(): void
    {
        [$student, $qiraat, , $a, $b] = $this->setupPair();
        $this->get(route('session.narrations', [$student, $qiraat]))->assertOk()
            ->assertSee(route('session.mushaf.both', [$student, $qiraat]));
        $this->get(route('session.mushaf.both', [$student, $qiraat]))->assertOk()->assertViewHas('page', 482);
        $global = $a->progress->global_ayah_number;
        $this->post(route('session.confirm-both', [$student, $qiraat]), [
            'surah_number' => 41, 'ayah_number' => 54, 'global_ayah_number' => $global, 'page_number' => 482,
        ])->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();
        foreach ([$a, $b] as $attempt) {
            $this->assertDatabaseHas('ayah_confirmations', ['recitation_attempt_id' => $attempt->id, 'global_ayah_number' => $global]);
            $this->assertSame(482, $attempt->progress->fresh()->page_number);
        }
        $this->assertSame(1, $a->fresh()->attempt_number);
        $this->assertSame(2, $b->fresh()->attempt_number);
        $this->assertSame(1, $student->attempts()->whereNotNull('completed_at')->count());
    }

    public function test_different_ayahs_block_opening_and_saving_paired_sessions(): void
    {
        [$student, $qiraat, , $a, $b] = $this->setupPair();
        $this->get(route('session.mushaf.both', [$student, $qiraat]))->assertOk();
        $b->progress->update(['ayah_number' => 53, 'global_ayah_number' => $a->progress->global_ayah_number - 1]);
        $this->get(route('session.narrations', [$student, $qiraat]))->assertOk()
            ->assertDontSee(route('session.mushaf.both', [$student, $qiraat]))->assertSee('aria-describedby="paired-reading-hint"', false);
        $this->get(route('session.mushaf.both', [$student, $qiraat]))->assertRedirect()->assertSessionHasErrors('paired_readings');
        $this->post(route('session.confirm-both', [$student, $qiraat]), [
            'surah_number' => 41, 'ayah_number' => 54, 'global_ayah_number' => $a->progress->global_ayah_number, 'page_number' => 482,
        ])->assertSessionHasErrors('paired_readings');
        $this->assertDatabaseCount('ayah_confirmations', 0);
    }

    public function test_direct_entry_resolves_the_saved_ayah_page(): void
    {
        [$student, , $first] = $this->setupPair();
        $this->get(route('session.mushaf', [$student, $first]))->assertOk()
            ->assertViewHas('page', 482)->assertSee('mushaf-ayah is-last', false)->assertSee('آية الاختبار');
        Http::assertSent(fn ($request) => str_contains($request->url(), '/ayah/'));
    }

    public function test_explicit_page_navigation_does_not_resolve_the_saved_ayah(): void
    {
        [$student, , $first] = $this->setupPair();
        $this->get(route('session.mushaf', [$student, $first, 'page' => 477]))->assertOk()->assertViewHas('page', 477);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/ayah/'));
    }

    public function test_correct_saved_page_needs_no_ayah_lookup(): void
    {
        [$student, , $first, $a] = $this->setupPair();
        $a->progress->update(['page_number' => 482]);
        $this->get(route('session.mushaf', [$student, $first]))->assertOk()->assertViewHas('page', 482);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/ayah/'));
    }

    public function test_failed_ayah_lookup_shows_retry_state(): void
    {
        [$student, , $first] = $this->setupPair(true);
        $this->get(route('session.mushaf', [$student, $first]))->assertOk()
            ->assertSee('تعذر تحديد صفحة آخر آية مؤكدة.')->assertSee('إعادة المحاولة');
    }
}
