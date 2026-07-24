<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AyahConfirmation extends Model
{
    protected $guarded = [];
    protected $casts = ['confirmed_at' => 'datetime'];
    public function session(): BelongsTo { return $this->belongsTo(RecitationSession::class, 'recitation_session_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function qiraat(): BelongsTo { return $this->belongsTo(Qiraat::class); }
}
