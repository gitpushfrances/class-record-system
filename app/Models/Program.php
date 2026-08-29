<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Program extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'department_id',
        'name',
        'code',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function programHead()
    {
        return $this->hasOne(User::class)->where('role', 'program_head');
    }

    public function activeFacultyIds()
    {
        $adviserIds = SectionTerm::where('status', 'active')
            ->whereHas('section', fn ($q) => $q->where('program_id', $this->id))
            ->whereNotNull('adviser_id')
            ->pluck('adviser_id');

        $teacherIds = DB::table('section_subject_teachers')
            ->join('section_terms', 'section_terms.id', '=', 'section_subject_teachers.section_term_id')
            ->join('sections', 'sections.id', '=', 'section_terms.section_id')
            ->where('sections.program_id', $this->id)
            ->where('section_terms.status', 'active')
            ->pluck('section_subject_teachers.teacher_id');

        return $adviserIds->merge($teacherIds)->unique()->values();
    }

    public function activeStudentCount()
    {
        return Enrollment::where('status', 'enrolled')
            ->whereHas('sectionTerm', function ($q) {
                $q->active()->whereHas('section', function ($q2) {
                    $q2->where('program_id', $this->id);
                });
            })
            ->count();
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
