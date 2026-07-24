<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssuedCertificate extends Model
{
    protected $fillable = ['certificate_template_id', 'student_id', 'issued_by', 'student_name_snapshot', 'image_path', 'metadata_json', 'issued_at'];
    protected $casts = ['metadata_json' => 'array', 'issued_at' => 'datetime'];

    public function template(): BelongsTo { return $this->belongsTo(CertificateTemplate::class, 'certificate_template_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function issuer(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
}
