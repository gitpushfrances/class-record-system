<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicPeriod extends Model
{
    protected $fillable = ['school_year', 'semester', 'midterm_cutoff_date', 'finals_cutoff_date', 'is_active'];

    protected $casts = [
        'midterm_cutoff_date' => 'date',
        'finals_cutoff_date'  => 'date',
    ];

    public static function getActive()
    {
        return static::where('is_active', true)->first();
    }
}
