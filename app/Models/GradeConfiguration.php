<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'section_id',
        'quiz_weight',
        'exam_weight',
        'project_weight',
        'assessment_weight',
        'attendance_weight',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'quiz_weight' => 'decimal:2',
        'exam_weight' => 'decimal:2',
        'project_weight' => 'decimal:2',
        'assessment_weight' => 'decimal:2',
        'attendance_weight' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Check if weights sum to 100
    public function isValidConfiguration()
    {
        $total = $this->quiz_weight + $this->exam_weight + $this->project_weight +
                 $this->assessment_weight + $this->attendance_weight;
        return abs($total - 100) < 0.01; // Allow small floating point differences
    }
}
