<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function progress(): HasMany { return $this->hasMany(MemorizationProgress::class); }
    public function confirmations(): HasMany { return $this->hasMany(AyahConfirmation::class); }
    public function attempts(): HasMany { return $this->hasMany(RecitationAttempt::class); }
    public function issuedCertificates(): HasMany { return $this->hasMany(IssuedCertificate::class); }
}
