<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'enrollment_id',
        'subject_id',
        'date',
        'session_type',
        'status',
        'remarks',
        'recorded_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public const ABSENCE_WARNING = 3;

    public static function consecutiveAbsences($records): int
    {
        $ordered = $records->sortBy(fn($r) => [$r->date->toDateString(), $r->session_type === 'laboratory' ? 1 : 0])->reverse();
        $streak  = 0;

        foreach ($ordered as $record) {
            if ($record->status !== 'absent') break;
            $streak++;
        }

        return $streak;
    }

    // Relationships
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
