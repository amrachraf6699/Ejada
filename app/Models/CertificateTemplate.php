<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateTemplate extends Model
{
    protected $fillable = ['name', 'background_path', 'width', 'height', 'canvas_json', 'is_active'];
    protected $casts = ['canvas_json' => 'array', 'is_active' => 'boolean'];

    public function issuedCertificates(): HasMany
    {
        return $this->hasMany(IssuedCertificate::class);
    }
}
