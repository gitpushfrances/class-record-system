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

    public static function sortRoster($enrollments, $gender = null, $order = null)
    {
        $rank = ['male' => 0, 'female' => 1];

        if (in_array($gender, ['male', 'female'], true)) {
            $enrollments = $enrollments->filter(fn ($e) => $e->student?->gender === $gender);
        }

        return $enrollments
            ->sortBy(fn ($e) => [
                $rank[$e->student?->gender] ?? 2,
                $order === 'id'
                    ? strtolower($e->student?->student_number ?? '')
                    : strtolower($e->student?->last_name ?? ''),
                strtolower($e->student?->first_name ?? ''),
            ])
            ->values();
    }

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
