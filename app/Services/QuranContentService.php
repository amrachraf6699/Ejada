<?php

namespace App\Services;

use App\Exceptions\QuranApiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class QuranContentService
{
    private const AYAH_COUNTS = [7,286,200,176,120,165,206,75,129,109,123,111,43,52,99,128,111,110,98,135,112,78,118,64,77,227,93,88,69,60,34,30,73,54,45,83,182,88,75,85,54,53,89,59,37,35,38,29,18,45,60,49,62,55,78,96,29,22,24,13,14,11,11,18,12,12,30,52,52,44,28,28,20,56,40,31,50,40,46,42,29,19,36,25,22,17,19,26,30,20,15,21,11,8,8,19,5,8,8,11,11,8,3,9,5,4,7,3,6,3,5,4,5,6];

    public function surahName(?int $surahNumber): string
    {
        return config('quran.surah_names.' . $surahNumber, 'سورة غير معروفة');
    }

    public function locationForGlobalAyah(int $globalAyahNumber): array
    {
        abort_if($globalAyahNumber < 1 || $globalAyahNumber > array_sum(self::AYAH_COUNTS), 422);
        $remaining = $globalAyahNumber;
        foreach (self::AYAH_COUNTS as $index => $count) {
            if ($remaining <= $count) {
                return ['surah_number' => $index + 1, 'ayah_number' => $remaining];
            }
            $remaining -= $count;
        }
        abort(422);
    }

    public function globalAyahNumber(int $surahNumber, int $ayahNumber): int
    {
        $count = self::AYAH_COUNTS[$surahNumber - 1] ?? null;
        if (! $count || $ayahNumber < 1 || $ayahNumber > $count) {
            throw new \InvalidArgumentException('رقم السورة أو الآية غير صحيح.');
        }
        return array_sum(array_slice(self::AYAH_COUNTS, 0, $surahNumber - 1)) + $ayahNumber;
    }

    public function page(int $page): array
    {
        return Cache::remember("quran.page.{$page}.quran-uthmani", now()->addDay(), function () use ($page) {
            try {
                $response = Http::acceptJson()->timeout(config('services.quran.timeout'))->get($this->baseUrl() . "/page/{$page}/quran-uthmani");
            } catch (\Throwable) {
                throw new QuranApiException('حدث خطأ أثناء الاتصال بمزود القرآن.');
            }
            if ($response->failed()) {
                throw new QuranApiException('تعذر تحميل صفحة المصحف من مزود القرآن.');
            }
            $data = $response->json('data', []);
            return [
                'page' => $data['number'] ?? $page,
                'ayahs' => collect($data['ayahs'] ?? [])->map(fn (array $ayah) => [
                    'id' => $ayah['number'] ?? null,
                    'surah_number' => $ayah['surah']['number'] ?? null,
                    'surah_name' => $this->surahName($ayah['surah']['number'] ?? null),
                    'ayah_number' => $ayah['numberInSurah'] ?? null,
                    'global_ayah_number' => $ayah['number'] ?? null,
                    'page_number' => $ayah['page'] ?? $page,
                    'text' => $ayah['text'] ?? '',
                ])->all(),
            ];
        });
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.quran.base_url', 'https://api.alquran.cloud/v1'), '/');
    }
}
