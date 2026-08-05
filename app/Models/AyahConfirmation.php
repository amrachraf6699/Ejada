<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AyahConfirmation extends Model
{
    protected $guarded = [];
    protected $casts = ['confirmed_at' => 'datetime'];
    protected $appends = ['surah_name'];

    public function getSurahNameAttribute(): string
    {
        return config('quran.surah_names.' . $this->surah_number, 'سورة غير معروفة');
    }

    public function session(): BelongsTo { return $this->belongsTo(RecitationSession::class, 'recitation_session_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function narration(): BelongsTo { return $this->belongsTo(Narration::class); }
}
