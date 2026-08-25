<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentNumberCounter extends Model
{
    protected $fillable = [
        'year',
        'last_number',
    ];
}
