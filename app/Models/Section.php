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

    // Grade items and grade configurations belong to a term: see SectionTerm::gradeItemsFor() and gradeConfigurationFor().

    public function getFullNameAttribute()
    {
        return $this->program->code . ' ' . $this->year_number . '-' . $this->section_letter;
    }
}
