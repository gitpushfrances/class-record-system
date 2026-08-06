<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'section_term_id',
        'status',
        'enrolled_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function sectionTerm()
    {
        return $this->belongsTo(SectionTerm::class);
    }

    public function studentGrades()
    {
        return $this->hasMany(StudentGrade::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function finalGrade()
    {
        return $this->hasOne(FinalGrade::class);
    }
}
