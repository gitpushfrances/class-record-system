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
        'subject_id',
        'midterm_percentage',
        'midterm_numerical',
        'final_percentage',
        'final_numerical',
        'average_numerical',
        'final_grade',
        'numerical_grade',
        'letter_grade',
        'remarks',
        'is_locked',
        'computed_by',
        'locked_at',
    ];

    protected $casts = [
        'midterm_percentage' => 'decimal:2',
        'midterm_numerical'  => 'decimal:2',
        'final_percentage'   => 'decimal:2',
        'final_numerical'    => 'decimal:2',
        'average_numerical'  => 'decimal:2',
        'final_grade'        => 'decimal:2',
        'numerical_grade'    => 'decimal:2',
        'is_locked'          => 'boolean',
        'locked_at'          => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function computedBy()
    {
        return $this->belongsTo(User::class, 'computed_by');
    }

    public static function convertToNumericalGrade($percentage)
    {
        $percentage = (float) $percentage;

        // Flat zones — match Scholaro's Most Common Tertiary table exactly,
        // no interpolation (interpolating these produces a 3.99-vs-4.00
        // inversion between a barely-passing and a conditional-failing score)
        if ($percentage >= 75 && $percentage < 77) return 3.00;
        if ($percentage >= 70 && $percentage < 75) return 4.00;
        if ($percentage < 70) return 5.00;

        // Interpolated zones (77%-100%), linear within each band,
        // rounded to nearest 0.1 per client decision
        $bands = [
            ['pct_min' => 96.00, 'pct_max' => 100.00, 'grade_min' => 1.00, 'grade_max' => 1.24],
            ['pct_min' => 94.00, 'pct_max' => 95.99,  'grade_min' => 1.25, 'grade_max' => 1.49],
            ['pct_min' => 91.00, 'pct_max' => 93.99,  'grade_min' => 1.50, 'grade_max' => 1.74],
            ['pct_min' => 89.00, 'pct_max' => 90.99,  'grade_min' => 1.75, 'grade_max' => 1.99],
            ['pct_min' => 86.00, 'pct_max' => 88.99,  'grade_min' => 2.00, 'grade_max' => 2.24],
            ['pct_min' => 83.00, 'pct_max' => 85.99,  'grade_min' => 2.25, 'grade_max' => 2.49],
            ['pct_min' => 80.00, 'pct_max' => 82.99,  'grade_min' => 2.50, 'grade_max' => 2.74],
            ['pct_min' => 77.00, 'pct_max' => 79.99,  'grade_min' => 2.75, 'grade_max' => 2.99],
        ];

        foreach ($bands as $band) {
            if ($percentage >= $band['pct_min'] && $percentage <= $band['pct_max']) {
                $span = $band['pct_max'] - $band['pct_min'];
                $grade = $span > 0
                    ? $band['grade_max'] - ($band['grade_max'] - $band['grade_min']) * (($percentage - $band['pct_min']) / $span)
                    : $band['grade_min'];
                return round($grade, 1);
            }
        }

        return 1.00; // safety net, should be unreachable given the bands above
    }
}
