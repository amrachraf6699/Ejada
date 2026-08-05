<?php

namespace App\Services;

use App\Models\AyahConfirmation;
use App\Models\CertificateTemplate;
use App\Models\IssuedCertificate;
use App\Models\MemorizationProgress;
use App\Models\Narration;
use App\Models\Qiraat;
use App\Models\RecitationSession;
use App\Models\Student;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\File;
use ZipArchive;

class AnalyticsReportService
{
    public function generate(): array
    {
        $directory = storage_path('app/reports/' . now('Africa/Cairo')->format('Ymd_His'));
        if (! is_dir($directory)) mkdir($directory, 0775, true);
        $students = Student::with(['progress.narration.qiraat'])->withCount(['confirmations', 'progress', 'issuedCertificates'])->orderBy('name')->get();
        $system = [
            ['الوحدة', 'العدد'], ['الطلاب', Student::count()], ['القراءات', Qiraat::count()], ['الروايات', Narration::count()], ['جلسات التسميع', RecitationSession::count()], ['تأكيدات التسميع', AyahConfirmation::count()], ['قوالب الشهادات', CertificateTemplate::count()], ['الشهادات الصادرة', IssuedCertificate::count()],
        ];
        $studentRows = [['اسم الطالب', 'سجلات التقدم', 'إجمالي الآيات', 'الروايات المكتملة', 'جلسات التسميع', 'تأكيدات التسميع', 'الشهادات', 'آخر نشاط']];
        foreach ($students as $student) $studentRows[] = [$student->name, $student->progress_count, $student->progress->sum('global_ayah_number'), $student->progress->filter->is_complete->count(), RecitationSession::where('student_id', $student->id)->count(), $student->confirmations_count, $student->issued_certificates_count, optional($student->confirmations->sortByDesc('confirmed_at')->first())->confirmed_at?->format('Y-m-d H:i') ?? ''];
        $narrationRows = [['اسم الطالب', 'القراءة', 'الرواية', 'رقم السورة', 'رقم الآية', 'الآية العامة', 'رقم الصفحة', 'النسبة', 'الحالة']];
        foreach (MemorizationProgress::with('student', 'narration.qiraat')->get() as $progress) $narrationRows[] = [$progress->student->name, $progress->narration->qiraat->name, $progress->narration->name, $progress->surah_number, $progress->ayah_number, $progress->global_ayah_number, $progress->page_number, round($progress->global_ayah_number / 6236 * 100, 2) . '%', $progress->is_complete ? 'مكتمل' : 'قيد التقدم'];
        $certificateRows = [['اسم الطالب', 'قالب الشهادة', 'أصدرها', 'تاريخ الإصدار']];
        foreach (IssuedCertificate::with('student', 'template', 'issuer')->orderByDesc('issued_at')->get() as $certificate) $certificateRows[] = [$certificate->student_name_snapshot, $certificate->template?->name ?? '', $certificate->issuer?->name ?? '', $certificate->issued_at?->format('Y-m-d H:i') ?? ''];
        $files = [
            $this->csv($directory . '/ملخص_النظام.csv', $system),
            $this->csv($directory . '/إحصائيات_الطلاب.csv', $studentRows),
            $this->csv($directory . '/تقدم_الروايات.csv', $narrationRows),
            $this->csv($directory . '/الشهادات_الصادرة.csv', $certificateRows),
        ];
        $zipPath = $directory . '/تقارير_المحفظ.zip'; $zip = new ZipArchive(); $zip->open($zipPath, ZipArchive::CREATE); foreach ($files as $file) $zip->addFile($file, basename($file)); $zip->close();
        $pdfPath = $directory . '/ملخص_المحفظ.pdf';
        $tempDirectory = storage_path('app/mpdf'); File::ensureDirectoryExists($tempDirectory);
        $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'default_font' => 'dejavusans', 'tempDir' => $tempDirectory]);
        $mpdf->SetDirectionality('rtl');
        $rows = implode('', array_map(fn ($row) => '<tr><td>' . htmlspecialchars((string) $row[0]) . '</td><td>' . htmlspecialchars((string) $row[1]) . '</td></tr>', array_slice($system, 1)));
        $studentSummary = implode('', array_map(fn ($row) => '<tr><td>' . htmlspecialchars((string) $row[0]) . '</td><td>' . htmlspecialchars((string) $row[2]) . '</td><td>' . htmlspecialchars((string) $row[3]) . '</td></tr>', array_slice($studentRows, 1)));
        $mpdf->WriteHTML("<html dir='rtl'><style>body{font-family:dejavusans}table{width:100%;border-collapse:collapse}td,th{border:1px solid #bbb;padding:7px;text-align:right}</style><h1>تقرير المحفظ</h1><p>" . now('Africa/Cairo')->translatedFormat('j F Y، H:i') . "</p><h2>ملخص الوحدات</h2><table><tr><th>الوحدة</th><th>العدد</th></tr>{$rows}</table><h2>إحصائيات الطلاب</h2><table><tr><th>الطالب</th><th>إجمالي الآيات</th><th>الروايات المكتملة</th></tr>{$studentSummary}</table></html>");
        $mpdf->Output($pdfPath, 'F');
        return compact('zipPath', 'pdfPath', 'directory');
    }

    public function send(): array
    {
        $report = $this->generate(); $telegram = app(TelegramService::class);
        $telegram->sendDocument($report['pdfPath'], 'تقرير المحفظ التحليلي بصيغة PDF');
        $telegram->sendDocument($report['zipPath'], 'ملفات CSV عربية لتقرير المحفظ');
        return $report;
    }

    private function csv(string $path, array $rows): string { $handle = fopen($path, 'wb'); fwrite($handle, "\xEF\xBB\xBF"); foreach ($rows as $row) fputcsv($handle, $row); fclose($handle); return $path; }
}
