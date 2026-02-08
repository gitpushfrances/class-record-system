<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationships
    public function assignedSections()
    {
        return $this->hasMany(Section::class, 'teacher_id');
    }

    public function approvedUsers()
    {
        return $this->hasMany(User::class, 'approved_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdGradeItems()
    {
        return $this->hasMany(GradeItem::class, 'created_by');
    }

    public function recordedGrades()
    {
        return $this->hasMany(StudentGrade::class, 'recorded_by');
    }

    public function recordedAttendance()
    {
        return $this->hasMany(AttendanceRecord::class, 'recorded_by');
    }

    // Role check helpers
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    public function isDean()
    {
        return $this->role === 'dean';
    }

    public function isTeacher()
    {
        return $this->role === 'teacher';
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }
}
