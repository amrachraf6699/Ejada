<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Narration extends Model
{
    protected $fillable = ['qiraat_id', 'name', 'sort_order'];

    public function qiraat(): BelongsTo { return $this->belongsTo(Qiraat::class); }
    public function progress(): HasMany { return $this->hasMany(MemorizationProgress::class); }
    public function sessions(): HasMany { return $this->hasMany(RecitationSession::class); }
    public function confirmations(): HasMany { return $this->hasMany(AyahConfirmation::class); }
    public function attempts(): HasMany { return $this->hasMany(RecitationAttempt::class); }
}
