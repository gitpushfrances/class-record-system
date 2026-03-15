<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
