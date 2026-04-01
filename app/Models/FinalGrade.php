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

    public function computedBy()
    {
        return $this->belongsTo(User::class, 'computed_by');
    }

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
        return 5.00;
    }
}
