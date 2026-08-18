<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_term_id',
        'subject_id',
        'status',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function sectionTerm()
    {
        return $this->belongsTo(SectionTerm::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
