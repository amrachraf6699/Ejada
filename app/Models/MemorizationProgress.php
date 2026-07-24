<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemorizationProgress extends Model
{
    protected $table = 'memorization_progress';
    protected $guarded = [];
    protected $casts = ['last_confirmed_at' => 'datetime'];
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function qiraat(): BelongsTo { return $this->belongsTo(Qiraat::class); }
}
