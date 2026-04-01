<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SectionTerm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'section_id',
        'adviser_id',
        'academic_year',
        'semester',
        'status',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function adviser()
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function verification()
    {
        return $this->hasOne(\App\Models\GradeVerification::class, 'section_term_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments', 'section_term_id', 'student_id')
            ->withPivot('status', 'enrolled_at')
            ->withTimestamps();
    }
}
