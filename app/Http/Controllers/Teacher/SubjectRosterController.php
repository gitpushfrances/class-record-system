<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectEnrollment;
use Illuminate\Http\Request;

/**
 * The subject teacher's class list: add any student (irregulars, retakes,
 * other programs) to this subject only, drop a student from this subject,
 * or restore a dropped one. Locked once grades are submitted.
 */
class SubjectRosterController extends Controller
{
    public const MIN_SEARCH = 3;
    public const MAX_RESULTS = 20;

    public function index(Request $request, Section $section, Subject $subject)
    {
        $term = $this->authorizeSubject($section, $subject);

        $section->load('program');
        $roster  = Enrollment::sortRoster($term->rosterFor($subject->id)->with('student.program')->get());
        $dropped = Enrollment::sortRoster($term->droppedFrom($subject->id)->with('student.program')->get());

        $query   = trim((string) $request->query('q', ''));
        $results = collect();

        if (mb_strlen($query) >= self::MIN_SEARCH) {
            $like    = '%' . addcslashes($query, '%_\\') . '%';
            $results = Student::with('program')
                ->where('status', 'active')
                ->whereNotIn('id', $roster->pluck('student_id'))
                ->where(fn ($q) => $q->where('student_number', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like]))
                ->orderBy('last_name')->orderBy('first_name')
                ->limit(self::MAX_RESULTS)
                ->get();
        }

        $locked    = $this->isLocked($term, $subject);
        $removable = $locked ? [] : $roster->filter(fn ($e) => $e->type === Enrollment::TYPE_IRREGULAR && !$this->hasRecords($e, $subject))->pluck('id')->all();

        return view('teacher.classes.students', compact('section', 'subject', 'term', 'roster', 'dropped', 'query', 'results', 'locked', 'removable'));
    }

    public function store(Request $request, Section $section, Subject $subject)
    {
        $term = $this->authorizeUnlocked($section, $subject);

        $request->validate(['student_id' => 'required|exists:students,id']);
        $student = Student::findOrFail($request->student_id);

        $enrollment = Enrollment::where('section_term_id', $term->id)->where('student_id', $student->id)->first();

        if ($enrollment && $enrollment->type === Enrollment::TYPE_REGULAR && $enrollment->status === Enrollment::STATUS_DROPPED) {
            return back()->with('error', "{$student->full_name} was dropped from this section. Ask the Program Head or Dean to re-enroll them.");
        }

        if ($term->rosterFor($subject->id)->where('student_id', $student->id)->exists()) {
            return back()->with('error', "{$student->full_name} is already in this class.");
        }

        $enrollment ??= Enrollment::create([
            'student_id'      => $student->id,
            'section_term_id' => $term->id,
            'type'            => Enrollment::TYPE_IRREGULAR,
            'status'          => Enrollment::STATUS_ENROLLED,
            'enrolled_at'     => now(),
        ]);

        $this->setStatus($enrollment, $subject, Enrollment::STATUS_ENROLLED);

        return back()->with('success', "{$student->full_name} added to {$subject->code}.");
    }

    public function destroy(Section $section, Subject $subject, Enrollment $enrollment)
    {
        $term = $this->authorizeUnlocked($section, $subject);
        abort_unless($term->rosterFor($subject->id)->whereKey($enrollment->id)->exists(), 404);

        $this->setStatus($enrollment, $subject, Enrollment::STATUS_DROPPED);

        return back()->with('success', "{$enrollment->student->full_name} dropped from {$subject->code}. Grades and attendance are kept.");
    }

    /** Undo adding an irregular student to this subject by mistake: only while they have no scores, attendance or final grade in it. */
    public function remove(Section $section, Subject $subject, Enrollment $enrollment)
    {
        $term = $this->authorizeUnlocked($section, $subject);
        abort_unless(
            (int) $enrollment->section_term_id === (int) $term->id
                && $enrollment->type === Enrollment::TYPE_IRREGULAR
                && $enrollment->subjectEnrollments()->where('subject_id', $subject->id)->where('status', Enrollment::STATUS_ENROLLED)->exists(),
            404
        );

        $name = $enrollment->student->full_name;
        if ($this->hasRecords($enrollment, $subject)) {
            return back()->with('error', "{$name} already has scores, attendance or a final grade in {$subject->code}. Use Drop instead: the records are kept.");
        }

        $enrollment->subjectEnrollments()->where('subject_id', $subject->id)->delete();
        if (!$enrollment->subjectEnrollments()->exists()) {
            $enrollment->delete();
        }

        return back()->with('success', "{$name} removed from {$subject->code}.");
    }

    private function hasRecords(Enrollment $enrollment, Subject $subject): bool
    {
        return \App\Models\StudentGrade::withTrashed()->where('enrollment_id', $enrollment->id)
                ->whereIn('grade_item_id', \Illuminate\Support\Facades\DB::table('grade_items')->where('subject_id', $subject->id)->select('id'))->exists()
            || \App\Models\AttendanceRecord::withTrashed()->where('enrollment_id', $enrollment->id)->where('subject_id', $subject->id)->exists()
            || \App\Models\FinalGrade::withTrashed()->where('enrollment_id', $enrollment->id)->where('subject_id', $subject->id)->exists();
    }

    public function restore(Section $section, Subject $subject, Enrollment $enrollment)
    {
        $term = $this->authorizeUnlocked($section, $subject);
        abort_unless(
            (int) $enrollment->section_term_id === (int) $term->id
                && $enrollment->status !== Enrollment::STATUS_DROPPED
                && $enrollment->subjectEnrollments()->where('subject_id', $subject->id)->where('status', Enrollment::STATUS_DROPPED)->exists(),
            404
        );

        $this->setStatus($enrollment, $subject, Enrollment::STATUS_ENROLLED);

        return back()->with('success', "{$enrollment->student->full_name} restored to {$subject->code}.");
    }

    private function setStatus(Enrollment $enrollment, Subject $subject, string $status): void
    {
        SubjectEnrollment::updateOrCreate(
            ['enrollment_id' => $enrollment->id, 'subject_id' => $subject->id],
            ['status' => $status, 'changed_by' => auth()->id()]
        );
    }

    private function authorizeSubject(Section $section, Subject $subject): SectionTerm
    {
        $term = $section->terms()->where('status', 'active')->first();
        abort_if(!$term, 403, 'No active term for this section.');

        $teaches = $term->subjects()
            ->wherePivot('teacher_id', auth()->id())
            ->where('subjects.id', $subject->id)
            ->exists();
        abort_if(!$teaches, 403, 'You are not assigned to teach this subject in this section.');

        return $term;
    }

    private function authorizeUnlocked(Section $section, Subject $subject): SectionTerm
    {
        $term = $this->authorizeSubject($section, $subject);
        abort_if($this->isLocked($term, $subject), 403, 'Grades are submitted. The class list is locked.');

        return $term;
    }

    private function isLocked(SectionTerm $term, Subject $subject): bool
    {
        return $term->verifications()->where('subject_id', $subject->id)->whereIn('status', ['pending', 'verified'])->exists();
    }
}
