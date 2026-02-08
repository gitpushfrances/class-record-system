<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentGrade extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'enrollment_id',
        'grade_item_id',
        'score',
        'remarks',
        'recorded_by',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    // Relationships
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function gradeItem()
    {
        return $this->belongsTo(GradeItem::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function changeLogs()
    {
        return $this->hasMany(GradeChangeLog::class);
    }

    // Get percentage
    public function getPercentageAttribute()
    {
        if ($this->gradeItem->max_score > 0) {
            return ($this->score / $this->gradeItem->max_score) * 100;
        }
        return 0;
    }
}
