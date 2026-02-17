<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request, Section $section)
    {
        $this->authorizeSection($section);

        $date = $request->input('date', today()->toDateString());

        $enrollments = $section->enrollments()
            ->with([
                'student',
                'attendanceRecords' => fn($q) => $q->whereDate('date', $date),
            ])
            ->get();

        return view('teacher.attendance.index', compact('section', 'enrollments', 'date'));
    }

    public function store(Request $request, Section $section)
    {
        $this->authorizeSection($section);

        $request->validate([
            'date'                    => 'required|date',
            'attendance'              => 'required|array',
            'attendance.*.enrollment_id' => 'required|exists:enrollments,id',
            'attendance.*.status'     => 'required|in:present,absent,late,excused',
        ]);

        DB::transaction(function () use ($request, $section) {
            foreach ($request->attendance as $entry) {
                AttendanceRecord::updateOrCreate(
                    [
                        'enrollment_id' => $entry['enrollment_id'],
                        'date'          => $request->date,
                    ],
                    [
                        'status'      => $entry['status'],
                        'remarks'     => $entry['remarks'] ?? null,
                        'recorded_by' => auth()->id(),
                    ]
                );
            }
        });

        return back()->with('success', 'Attendance saved.');
    }

    public function summary(Section $section)
    {
        $this->authorizeSection($section);

        $enrollments = $section->enrollments()
            ->with(['student', 'attendanceRecords'])
            ->get()
            ->map(function ($enrollment) {
                $records  = $enrollment->attendanceRecords;
                $total    = $records->count();
                $present  = $records->whereIn('status', ['present', 'late'])->count();
                $absent   = $records->where('status', 'absent')->count();
                $excused  = $records->where('status', 'excused')->count();
                $percent  = $total > 0 ? round(($present / $total) * 100, 2) : 0;

                return [
                    'enrollment' => $enrollment,
                    'student'    => $enrollment->student,
                    'total'      => $total,
                    'present'    => $present,
                    'absent'     => $absent,
                    'excused'    => $excused,
                    'percent'    => $percent,
                ];
            });

        return view('teacher.attendance.summary', compact('section', 'enrollments'));
    }

    private function authorizeSection(Section $section): void
    {
        abort_if($section->teacher_id !== auth()->id(), 403);
    }
}
