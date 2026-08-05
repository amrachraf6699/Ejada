<?php

namespace App\Services;

use App\Models\MemorizationProgress;
use App\Models\Narration;
use App\Models\Qiraat;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentProgressImportService
{
    private const HEADERS = ['اسم الطالب', 'القراءة', 'الرواية', 'رقم السورة', 'رقم الآية', 'رقم الصفحة'];

    public function import(UploadedFile $file): array
    {
        $rows = IOFactory::load($file->getRealPath())->getActiveSheet()->toArray(null, true, true, false);
        $headers = array_map(fn ($value) => $this->normalize((string) $value), array_shift($rows) ?? []);
        $indexes = [];
        foreach (self::HEADERS as $header) {
            $index = array_search($this->normalize($header), $headers, true);
            if ($index === false && $header !== 'رقم الصفحة') {
                throw new \InvalidArgumentException("العمود المطلوب غير موجود: {$header}");
            }
            $indexes[$header] = $index;
        }
        $qiraats = Qiraat::with('narrations')->get();
        $result = ['success' => [], 'skipped' => [], 'failed' => []];
        foreach ($rows as $offset => $row) {
            $rowNumber = $offset + 2;
            if (collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) continue;
            try {
                $studentName = $this->value($row, $indexes['اسم الطالب']);
                $qiraatName = $this->value($row, $indexes['القراءة']);
                $narrationName = $this->value($row, $indexes['الرواية']);
                $surah = $this->number($this->value($row, $indexes['رقم السورة']));
                $ayah = $this->number($this->value($row, $indexes['رقم الآية']));
                if ($studentName === '' || $qiraatName === '' || $narrationName === '') throw new \InvalidArgumentException('اسم الطالب والقراءة والرواية مطلوبة.');
                $qiraat = $qiraats->first(fn (Qiraat $item) => in_array($this->normalize($qiraatName), [$this->normalize($item->name), $this->normalize($item->imam)], true));
                if (! $qiraat) throw new \InvalidArgumentException('القراءة غير موجودة.');
                $narration = $qiraat->narrations->first(fn (Narration $item) => $this->normalize($item->name) === $this->normalize($narrationName));
                if (! $narration) throw new \InvalidArgumentException('الرواية غير موجودة ضمن القراءة المحددة.');
                $global = app(QuranContentService::class)->globalAyahNumber($surah, $ayah);
                $page = $indexes['رقم الصفحة'] === false ? null : $this->number($this->value($row, $indexes['رقم الصفحة']), false);
                if ($page !== null && ($page < 1 || $page > 604)) throw new \InvalidArgumentException('رقم الصفحة يجب أن يكون بين 1 و604.');
                $outcome = DB::transaction(function () use ($studentName, $narration, $surah, $ayah, $global, $page) {
                    $student = Student::all()->first(fn (Student $item) => $this->normalize($item->name) === $this->normalize($studentName)) ?? Student::create(['name' => $studentName]);
                    $progress = MemorizationProgress::firstOrNew(['student_id' => $student->id, 'narration_id' => $narration->id]);
                    if ($progress->exists && $progress->global_ayah_number >= $global) return ['status' => 'skipped', 'student' => $student->name, 'reason' => 'التقدم الحالي مساوي أو أبعد من الصف المستورد.'];
                    $progress->fill(['surah_number' => $surah, 'ayah_number' => $ayah, 'global_ayah_number' => $global, 'page_number' => $page ?? config("quran.surah_start_pages.{$surah}", 1), 'last_confirmed_at' => now()])->save();
                    return ['status' => 'success', 'student' => $student->name, 'reason' => $progress->wasRecentlyCreated ? 'تم إنشاء الطالب وحفظ تقدمه.' : 'تم تحديث تقدم الطالب.'];
                });
                $result[$outcome['status']][] = ['row' => $rowNumber, ...$outcome];
            } catch (\Throwable $exception) {
                $result['failed'][] = ['row' => $rowNumber, 'reason' => $exception->getMessage()];
            }
        }
        return $result;
    }

    private function value(array $row, int|false $index): string { return $index === false ? '' : trim((string) ($row[$index] ?? '')); }
    private function number(string $value, bool $required = true): ?int { $value = strtr($value, ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']); if ($value === '' && ! $required) return null; if (! ctype_digit($value)) throw new \InvalidArgumentException('القيم الرقمية غير صحيحة.'); return (int) $value; }
    private function normalize(string $value): string { return preg_replace('/\s+/u', ' ', trim(str_replace("\xEF\xBB\xBF", '', $value))); }
}
