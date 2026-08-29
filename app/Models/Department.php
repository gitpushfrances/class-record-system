<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'code', 'status'];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    public function dean()
    {
        return $this->hasOne(User::class)->where('role', 'dean');
    }

    public function activeFacultyIds()
    {
        $programIds = $this->programs()->pluck('id');

        $adviserIds = SectionTerm::where('status', 'active')
            ->whereHas('section', fn ($q) => $q->whereIn('program_id', $programIds))
            ->whereNotNull('adviser_id')
            ->pluck('adviser_id');

        $teacherIds = DB::table('section_subject_teachers')
            ->join('section_terms', 'section_terms.id', '=', 'section_subject_teachers.section_term_id')
            ->join('sections', 'sections.id', '=', 'section_terms.section_id')
            ->whereIn('sections.program_id', $programIds)
            ->where('section_terms.status', 'active')
            ->pluck('section_subject_teachers.teacher_id');

        return $adviserIds->merge($teacherIds)->unique()->values();
    }

    public function activeStudentCount()
    {
        $programIds = $this->programs()->pluck('id');

        return Enrollment::where('status', 'enrolled')
            ->whereHas('sectionTerm', function ($q) use ($programIds) {
                $q->active()->whereHas('section', function ($q2) use ($programIds) {
                    $q2->whereIn('program_id', $programIds);
                });
            })
            ->count();
    }
}
