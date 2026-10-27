<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A request to borrow one teacher of another department for one semester. */
class FacultyBorrowRequest extends Model
{
    public const PENDING   = 'pending';
    public const APPROVED  = 'approved';
    public const DECLINED  = 'declined';
    public const CANCELLED = 'cancelled';
    public const REVOKED   = 'revoked';

    protected $fillable = [
        'borrowing_department_id', 'lending_department_id', 'teacher_id', 'academic_year', 'semester',
        'status', 'message', 'decision_note', 'requested_by', 'decided_by', 'decided_at',
    ];

    protected $casts = ['decided_at' => 'datetime'];

    public function scopePending($query)
    {
        return $query->where('status', self::PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::APPROVED);
    }

    public function scopeForTerm($query, string $academicYear, string $semester)
    {
        return $query->where('academic_year', $academicYear)->where('semester', $semester);
    }

    public function borrowingDepartment()
    {
        return $this->belongsTo(Department::class, 'borrowing_department_id')->withTrashed();
    }

    public function lendingDepartment()
    {
        return $this->belongsTo(Department::class, 'lending_department_id')->withTrashed();
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
