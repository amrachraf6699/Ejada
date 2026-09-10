<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RecitationAttempt extends Model
{
    protected $guarded = [];
    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function narration(): BelongsTo { return $this->belongsTo(Narration::class); }
    public function progress(): HasOne { return $this->hasOne(MemorizationProgress::class); }
}
