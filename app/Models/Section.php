<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'program_id',
        'year_number',
        'section_letter',
        'year_level',
        'status',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function terms()
    {
        return $this->hasMany(SectionTerm::class);
    }

    public function currentTerm()
    {
        return $this->hasOne(SectionTerm::class)->where('status', 'active')->latestOfMany();
    }

    public function gradeItems()
    {
        return $this->hasMany(GradeItem::class);
    }

    public function gradeConfiguration()
    {
        return $this->hasOne(GradeConfiguration::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'section_subject_teachers')
            ->withPivot('teacher_id')
            ->withTimestamps();
    }

    public function assignedTeachers()
    {
        return $this->belongsToMany(User::class, 'section_subject_teachers', 'section_id', 'teacher_id')
            ->withPivot('subject_id')
            ->withTimestamps();
    }

    public function getFullNameAttribute()
    {
        return $this->program->code . ' ' . $this->year_number . '-' . $this->section_letter;
    }
}
