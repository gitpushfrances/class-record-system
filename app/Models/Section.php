<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subject_id',
        'teacher_id',
        'section_name',
        'year_level',
        'semester',
        'academic_year',
        'schedule',
        'room',
        'status',
    ];

    // Relationships
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments')
            ->withPivot('status', 'enrolled_at')
            ->withTimestamps();
    }

    public function gradeConfiguration()
    {
        return $this->hasOne(GradeConfiguration::class);
    }

    public function gradeItems()
    {
        return $this->hasMany(GradeItem::class);
    }

    // Accessor
    public function getFullNameAttribute()
    {
        return "{$this->subject->code} - {$this->section_name} ({$this->year_level})";
    }
}
