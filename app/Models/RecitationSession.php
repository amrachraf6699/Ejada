<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecitationSession extends Model
{
    protected $fillable = ['user_id', 'student_id', 'narration_id', 'started_at', 'ended_at'];
    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function narration(): BelongsTo { return $this->belongsTo(Narration::class); }
    public function confirmations(): HasMany { return $this->hasMany(AyahConfirmation::class); }
}
