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
        'type',
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

    public const TYPE_REGULAR    = 'regular';
    public const TYPE_IRREGULAR  = 'irregular';
    public const STATUS_ENROLLED = 'enrolled';
    public const STATUS_DROPPED  = 'dropped';

    public function subjectEnrollments()
    {
        return $this->hasMany(SubjectEnrollment::class);
    }

    /** Students taking a subject: section members not dropped from it, plus irregular students added to it. */
    public function scopeInRoster($query, int $subjectId)
    {
        return $query->where('enrollments.status', '!=', self::STATUS_DROPPED)
            ->where(function ($q) use ($subjectId) {
                $q->where(fn ($regular) => $regular
                        ->where('enrollments.type', self::TYPE_REGULAR)
                        ->whereDoesntHave('subjectEnrollments', fn ($s) => $s->where('subject_id', $subjectId)->where('status', self::STATUS_DROPPED)))
                  ->orWhere(fn ($irregular) => $irregular
                        ->where('enrollments.type', self::TYPE_IRREGULAR)
                        ->whereHas('subjectEnrollments', fn ($s) => $s->where('subject_id', $subjectId)->where('status', self::STATUS_ENROLLED)));
            });
    }

    /** Students dropped from a subject, or from the whole section; printed as "Dr" on the Report of Grades. */
    public function scopeDroppedFrom($query, int $subjectId)
    {
        return $query->where(function ($q) use ($subjectId) {
            $q->where(fn ($section) => $section
                    ->where('enrollments.type', self::TYPE_REGULAR)
                    ->where('enrollments.status', self::STATUS_DROPPED))
              ->orWhereHas('subjectEnrollments', fn ($s) => $s->where('subject_id', $subjectId)->where('status', self::STATUS_DROPPED));
        });
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
