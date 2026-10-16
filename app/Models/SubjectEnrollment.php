<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One student's status in one subject of a section term.
 * Regular students need a row only when dropped from that subject;
 * irregular students have a row for each subject they take.
 */
class SubjectEnrollment extends Model
{
    protected $fillable = ['enrollment_id', 'subject_id', 'status', 'changed_by'];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
