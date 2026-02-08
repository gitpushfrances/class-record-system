<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeChangeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_grade_id',
        'old_score',
        'new_score',
        'reason',
        'changed_by',
    ];

    protected $casts = [
        'old_score' => 'decimal:2',
        'new_score' => 'decimal:2',
    ];

    // Relationships
    public function studentGrade()
    {
        return $this->belongsTo(StudentGrade::class);
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
