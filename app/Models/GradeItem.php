<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'section_id',
        'component_type',
        'period',
        'name',
        'max_score',
        'date_given',
        'description',
        'is_locked',
        'created_by',
    ];

    protected $casts = [
        'max_score'  => 'decimal:2',
        'date_given' => 'date',
        'is_locked'  => 'boolean',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function studentGrades()
    {
        return $this->hasMany(StudentGrade::class);
    }
}
