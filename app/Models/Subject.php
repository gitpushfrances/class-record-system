<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'units',
        'year_level',
        'semester',
        'has_laboratory',
        'department',
        'program_id',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];

    protected $casts = [
        'units' => 'float',
        'has_laboratory' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public const YEAR_LEVELS = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];
    public const SEMESTERS   = ['1st Semester', '2nd Semester', 'Summer'];
    public const UNSPECIFIED = 'Year level / semester not set';

    /** "3rd Year · 1st Semester", or UNSPECIFIED until the Program Head sets both. */
    public function getPlacementAttribute(): string
    {
        return $this->year_level && $this->semester ? "{$this->year_level} · {$this->semester}" : self::UNSPECIFIED;
    }

    /** Subjects grouped by placement in curriculum order (1st Year 1st Sem first); unspecified ones last. */
    public static function byPlacement(\Illuminate\Support\Collection $subjects): \Illuminate\Support\Collection
    {
        $rank = fn ($list, $value) => $value === null ? 9 : array_search($value, $list, true);

        return $subjects
            ->sortBy(fn ($s) => sprintf('%d-%d-%s', $rank(self::YEAR_LEVELS, $s->year_level), $rank(self::SEMESTERS, $s->semester), $s->code))
            ->groupBy(fn ($s) => $s->placement);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sectionTerms()
    {
        return $this->belongsToMany(SectionTerm::class, 'section_subject_teachers')
            ->withPivot('id', 'teacher_id')
            ->withTimestamps();
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'section_subject_teachers', 'subject_id', 'teacher_id')
            ->withPivot('section_term_id')
            ->withTimestamps();
    }
}
