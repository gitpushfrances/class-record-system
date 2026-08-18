<?php
namespace App\Http\Controllers\Teacher;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AttendanceController extends Controller
{
    public function index(Request $request, Section $section, Subject $subject)
    {
        $this->authorizeSectionSubject($section, $subject);
        $date = $request->input('date', today()->toDateString());
        $currentTerm = $section->terms()->where('status', 'active')->first();
        $enrollments = collect();
        if ($currentTerm) {
            $enrollments = $currentTerm->enrollments()
                ->with([
                    'student',
                    'attendanceRecords' => fn($q) => $q
                        ->where('subject_id', $subject->id)
                        ->whereDate('date', $date),
                ])
                ->get();
        }
        return view('teacher.attendance.index', compact('section', 'subject', 'enrollments', 'date'));
    }
    public function store(Request $request, Section $section, Subject $subject)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);
        $this->assertNotLocked($subject, $currentTerm);
        $request->validate([
            'date'                       => 'required|date',
            'attendance'                 => 'required|array',
            'attendance.*.enrollment_id' => 'required|exists:enrollments,id',
            'attendance.*.status'        => 'required|in:present,absent,late,excused',
        ]);
        DB::transaction(function () use ($request, $subject) {
            foreach ($request->attendance as $entry) {
                AttendanceRecord::updateOrCreate(
                    [
                        'enrollment_id' => $entry['enrollment_id'],
                        'subject_id'    => $subject->id,
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
        return redirect()->route('teacher.classes.record', [$section, $subject])
            ->with('success', 'Attendance saved.');
    }
    public function summary(Section $section, Subject $subject)
    {
        $this->authorizeSectionSubject($section, $subject);
        $currentTerm = $section->terms()->where('status', 'active')->first();
        $enrollments = collect();
        if ($currentTerm) {
            $enrollments = $currentTerm->enrollments()
                ->with(['student', 'attendanceRecords' => fn($q) => $q->where('subject_id', $subject->id)])
                ->get()
                ->map(function ($enrollment) {
                    $records = $enrollment->attendanceRecords;
                    $total   = $records->count();
                    $present = $records->whereIn('status', ['present', 'late'])->count();
                    $absent  = $records->where('status', 'absent')->count();
                    $excused = $records->where('status', 'excused')->count();
                    $percent = $total > 0 ? round(($present / $total) * 100, 2) : 0;
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
        }
        return view('teacher.attendance.summary', compact('section', 'subject', 'enrollments'));
    }

    /**
     * Only the teacher assigned to THIS subject in this section's active term
     * may mark or view its attendance — same rule as grade access. Advisory
     * alone does not grant attendance access.
     */
    private function authorizeSectionSubject(Section $section, Subject $subject): SectionTerm
    {
        $currentTerm = $section->terms()->where('status', 'active')->first();
        abort_if(!$currentTerm, 403, 'No active term for this section.');

        $isSubjectTeacher = $currentTerm->subjects()
            ->wherePivot('teacher_id', auth()->id())
            ->where('subjects.id', $subject->id)
            ->exists();

        abort_if(!$isSubjectTeacher, 403, 'You are not assigned to teach this subject in this section.');

        return $currentTerm;
    }

    /**
     * Blocks attendance edits once this subject's grades have been submitted
     * (pending) or verified. Rejected submissions are excluded so the teacher
     * can correct and resubmit.
     */
    private function assertNotLocked(Subject $subject, SectionTerm $currentTerm): void
    {
        $locked = $currentTerm->verifications()
            ->where('subject_id', $subject->id)
            ->whereIn('status', ['pending', 'verified'])
            ->exists();

        abort_if($locked, 403, 'Grades for this subject are locked pending or after verification and attendance cannot be edited.');
    }
}
