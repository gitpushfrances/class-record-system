<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentNumberCounter extends Model
{
    protected $fillable = [
        'department_id',
        'program_id',
        'year',
        'last_number',
    ];
}
