<?php

namespace Tests\Feature;

use App\Models\Narration;
use App\Models\Qiraat;
use App\Models\Student;
use App\Models\User;
use App\Services\AnalyticsReportService;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use ZipArchive;

class ImportAndTelegramFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_creates_students_and_reports_partial_results(): void
    {
        $qiraat = Qiraat::create(['name' => 'عاصم الكوفي', 'imam' => 'عاصم الكوفي', 'is_available' => true]);
        $qiraat->narrations()->create(['name' => 'حفص', 'sort_order' => 1]);
        $user = User::factory()->create();
        $csv = "\xEF\xBB\xBFاسم الطالب,القراءة,الرواية,رقم السورة,رقم الآية,رقم الصفحة\nطالب مستورد,عاصم الكوفي,حفص,1,7,1\nطالب مستورد,عاصم الكوفي,حفص,1,3,1\nطالب خاطئ,عاصم الكوفي,رواية غير موجودة,1,1,1\n";
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $this->actingAs($user)->post(route('students.import.store'), ['file' => $file])
            ->assertRedirect(route('students.import.create'))
            ->assertSessionHas('import_result', fn (array $result) => count($result['success']) === 1 && count($result['skipped']) === 1 && count($result['failed']) === 1);
        $this->assertDatabaseHas('students', ['name' => 'طالب مستورد']);
        $this->assertDatabaseHas('memorization_progress', ['global_ayah_number' => 7]);
    }

    public function test_sqlite_backup_is_sent_to_telegram(): void
    {
        config(['services.telegram.bot_token' => 'token', 'services.telegram.chat_id' => 'chat']);
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true])]);

        $path = app(DatabaseBackupService::class)->send();

        $this->assertFileExists($path);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/bottoken/sendDocument'));
    }

    public function test_analytics_report_generates_arabic_csv_zip_and_pdf(): void
    {
        Student::create(['name' => 'طالب التقرير']);
        $report = app(AnalyticsReportService::class)->generate();

        $this->assertFileExists($report['zipPath']);
        $this->assertFileExists($report['pdfPath']);
        $zip = new ZipArchive(); $zip->open($report['zipPath']);
        $this->assertSame("\xEF\xBB\xBF", substr((string) $zip->getFromName('ملخص_النظام.csv'), 0, 3));
        $zip->close();
    }

    public function test_backup_and_report_commands_are_scheduled(): void
    {
        Artisan::call('schedule:list');
        $output = Artisan::output();
        $this->assertStringContainsString('database:backup-telegram', $output);
        $this->assertStringContainsString('analytics:report-telegram', $output);
    }
}
