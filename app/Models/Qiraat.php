<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Qiraat extends Model
{
    protected $fillable = ['name', 'imam', 'api_identifier', 'is_available'];
    protected $casts = ['is_available' => 'boolean'];

    public function narrations(): HasMany { return $this->hasMany(Narration::class)->orderBy('sort_order'); }
}
