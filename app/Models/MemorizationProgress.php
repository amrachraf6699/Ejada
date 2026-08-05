<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemorizationProgress extends Model
{
    public const TOTAL_AYAHS = 6236;

    protected $table = 'memorization_progress';
    protected $guarded = [];
    protected $casts = ['last_confirmed_at' => 'datetime'];
    protected $appends = ['surah_name'];

    public function getSurahNameAttribute(): string
    {
        return config('quran.surah_names.' . $this->surah_number, 'سورة غير معروفة');
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->global_ayah_number >= self::TOTAL_AYAHS;
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function narration(): BelongsTo { return $this->belongsTo(Narration::class); }
}
