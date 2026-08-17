<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'program_id',
        'year_number',
        'section_letter',
        'year_level',
        'status',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function terms()
    {
        return $this->hasMany(SectionTerm::class);
    }

    public function currentTerm()
    {
        return $this->hasOne(SectionTerm::class)->where('status', 'active')->latestOfMany();
    }

    public function gradeItems()
    {
        return $this->hasMany(GradeItem::class);
    }

    public function gradeItemsFor(int $subjectId)
    {
        return $this->gradeItems()->where('subject_id', $subjectId);
    }

    /**
     * @deprecated Old section-level design — returns whichever config row
     * happens to be found for this section, ignoring subject. Left in place
     * only in case something else still calls it; new code should use
     * gradeConfigurationFor() instead.
     */
    public function gradeConfiguration()
    {
        return $this->hasOne(GradeConfiguration::class);
    }

    public function gradeConfigurations()
    {
        return $this->hasMany(GradeConfiguration::class);
    }

    public function gradeConfigurationFor(int $subjectId): ?GradeConfiguration
    {
        return $this->gradeConfigurations()
            ->where('subject_id', $subjectId)
            ->first();
    }



    public function getFullNameAttribute()
    {
        return $this->program->code . ' ' . $this->year_number . '-' . $this->section_letter;
    }
}
