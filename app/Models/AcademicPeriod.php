<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicPeriod extends Model
{
    protected $fillable = ['school_year', 'semester', 'start_date', 'midterm_cutoff_date', 'finals_cutoff_date', 'is_active'];

    protected $casts = [
        'start_date'          => 'date',
        'midterm_cutoff_date' => 'date',
        'finals_cutoff_date'  => 'date',
    ];

    /** The period the Administrator set for a term's school year and semester, if any. */
    public static function forTerm(string $academicYear, string $semester): ?self
    {
        return static::where('school_year', $academicYear)->where('semester', $semester)->first();
    }

    public static function getActive()
    {
        return static::where('is_active', true)->first();
    }
}
