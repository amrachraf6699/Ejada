<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemorizationProgress extends Model
{
    protected $table = 'memorization_progress';
    protected $guarded = [];
    protected $casts = ['last_confirmed_at' => 'datetime'];
    protected $appends = ['surah_name'];

    public function getSurahNameAttribute(): string
    {
        return config('quran.surah_names.' . $this->surah_number, 'سورة غير معروفة');
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function qiraat(): BelongsTo { return $this->belongsTo(Qiraat::class); }
}
