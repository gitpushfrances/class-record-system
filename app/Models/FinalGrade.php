<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinalGrade extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'enrollment_id',
        'quiz_score',
        'exam_score',
        'project_score',
        'assessment_score',
        'attendance_score',
        'final_grade',
        'numerical_grade',
        'letter_grade',
        'remarks',
        'is_locked',
        'computed_by',
        'locked_at',
    ];

    protected $casts = [
        'quiz_score' => 'decimal:2',
        'exam_score' => 'decimal:2',
        'project_score' => 'decimal:2',
        'assessment_score' => 'decimal:2',
        'attendance_score' => 'decimal:2',
        'final_grade' => 'decimal:2',
        'numerical_grade' => 'decimal:2',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    // Relationships
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function computedBy()
    {
        return $this->belongsTo(User::class, 'computed_by');
    }

    // Convert percentage to Philippine grading scale (1.00 - 5.00)
    public static function convertToNumericalGrade($percentage)
    {
        if ($percentage >= 97) return 1.00;
        if ($percentage >= 94) return 1.25;
        if ($percentage >= 91) return 1.50;
        if ($percentage >= 88) return 1.75;
        if ($percentage >= 85) return 2.00;
        if ($percentage >= 82) return 2.25;
        if ($percentage >= 79) return 2.50;
        if ($percentage >= 76) return 2.75;
        if ($percentage >= 75) return 3.00;
        return 5.00; // Failed
    }
}
