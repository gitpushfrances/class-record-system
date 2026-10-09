<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_number',
        'first_name',
        'middle_name',
        'gender',
        'last_name',
        'email',
        'year_level',
        'program_id',
        'student_type',
        'status',
    ];

    // Relationships
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'enrollments')
            ->withPivot('status', 'enrolled_at')
            ->withTimestamps();
    }

    // Accessors
    public function getFullNameAttribute()
    {
        $middle  = trim((string) $this->middle_name);
        $initial = $middle !== '' ? ' ' . mb_strtoupper(mb_substr($middle, 0, 1)) . '.' : '';

        return trim("{$this->last_name}, {$this->first_name}{$initial}");
    }
}
