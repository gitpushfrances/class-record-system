<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SectionTerm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'section_id',
        'adviser_id',
        'academic_year',
        'semester',
        'status',
        'midterm_cutoff_date',
    ];

    protected $casts = [
        'midterm_cutoff_date' => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function adviser()
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    /** Section members: regular students not dropped from the section. */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class)
            ->where('enrollments.type', Enrollment::TYPE_REGULAR)
            ->where('enrollments.status', '!=', Enrollment::STATUS_DROPPED);
    }

    /** Students taking one subject in this term (see Enrollment::scopeInRoster). */
    public function rosterFor(int $subjectId)
    {
        return Enrollment::where('section_term_id', $this->id)->inRoster($subjectId);
    }

    /** Students dropped from one subject in this term (see Enrollment::scopeDroppedFrom). */
    public function droppedFrom(int $subjectId)
    {
        return Enrollment::where('section_term_id', $this->id)->droppedFrom($subjectId);
    }

    /** Puts the subject roster in the "enrollments" relation; eager loads are written as "enrollments.x". */
    public function loadRoster(int $subjectId, array $with = []): static
    {
        $nested = [];
        foreach ($with as $key => $value) {
            if (is_int($key)) {
                $nested[] = \Illuminate\Support\Str::after($value, 'enrollments.');
            } else {
                $nested[\Illuminate\Support\Str::after($key, 'enrollments.')] = $value;
            }
        }

        return $this->setRelation('enrollments', $this->rosterFor($subjectId)->with($nested)->get());
    }

    /** Grade items of one subject in this term (the Class Record columns). */
    public function gradeItemsFor(int $subjectId)
    {
        return $this->hasMany(GradeItem::class)->where('subject_id', $subjectId);
    }

    /** Grade configuration (components, weights, conversion) of one subject in this term. */
    public function gradeConfigurationFor(int $subjectId): ?GradeConfiguration
    {
        return $this->hasMany(GradeConfiguration::class)->where('subject_id', $subjectId)->first();
    }

    public function verifications()
    {
        return $this->hasMany(\App\Models\GradeVerification::class, 'section_term_id');
    }

    public function verificationFor(int $subjectId): ?\App\Models\GradeVerification
    {
        return $this->verifications()->where('subject_id', $subjectId)->first();
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments', 'section_term_id', 'student_id')
            ->withPivot('status', 'enrolled_at')
            ->withTimestamps();
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'section_subject_teachers')
            ->withPivot('id', 'teacher_id', 'schedule_days', 'schedule_start', 'schedule_end', 'lab_days', 'lab_start', 'lab_end')
            ->withTimestamps();
    }
}
