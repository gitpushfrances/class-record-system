<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\GradeVerification;
use App\Models\SectionTerm;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectEnrollment;
use App\Models\User;
use App\Services\ReportOfGrades;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RosterTest extends TestCase
{
    use RefreshDatabase;

    private SectionTerm $term;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $teacher       = User::where('email', 'teacher@classrecord.test')->firstOrFail();
        $this->term    = SectionTerm::where('status', 'active')
            ->whereHas('section.program', fn ($q) => $q->where('code', 'BSCS'))
            ->firstOrFail();
        $this->subject = Subject::firstOrFail();
        $this->term->subjects()->attach($this->subject->id, ['teacher_id' => $teacher->id]);
    }

    private function member(): Enrollment
    {
        return $this->term->enrollments()->with('student')->firstOrFail();
    }

    public function test_section_members_are_in_every_subject_roster(): void
    {
        $members = $this->term->enrollments()->count();

        $this->assertGreaterThan(0, $members);
        $this->assertSame($members, $this->term->rosterFor($this->subject->id)->count());
    }

    public function test_dropping_from_one_subject_keeps_the_section_membership(): void
    {
        $enrollment = $this->member();
        SubjectEnrollment::create(['enrollment_id' => $enrollment->id, 'subject_id' => $this->subject->id, 'status' => 'dropped']);

        $this->assertFalse($this->term->rosterFor($this->subject->id)->whereKey($enrollment->id)->exists());
        $this->assertTrue($this->term->droppedFrom($this->subject->id)->whereKey($enrollment->id)->exists());
        $this->assertTrue($this->term->enrollments()->whereKey($enrollment->id)->exists());
    }

    public function test_irregular_student_is_only_in_the_subject_they_take(): void
    {
        $other   = Subject::where('id', '!=', $this->subject->id)->firstOrFail();
        $student = Student::create([
            'student_number' => '99-0001',
            'first_name'     => 'Irene',
            'last_name'      => 'Regular',
            'gender'         => 'female',
            'year_level'     => '2nd Year',
            'student_type'   => 'irregular',
            'status'         => 'active',
        ]);
        $enrollment = Enrollment::create([
            'student_id'      => $student->id,
            'section_term_id' => $this->term->id,
            'type'            => Enrollment::TYPE_IRREGULAR,
            'status'          => Enrollment::STATUS_ENROLLED,
            'enrolled_at'     => now(),
        ]);
        SubjectEnrollment::create(['enrollment_id' => $enrollment->id, 'subject_id' => $this->subject->id, 'status' => 'enrolled']);

        $this->assertTrue($this->term->rosterFor($this->subject->id)->whereKey($enrollment->id)->exists());
        $this->assertFalse($this->term->rosterFor($other->id)->whereKey($enrollment->id)->exists());
        $this->assertFalse($this->term->enrollments()->whereKey($enrollment->id)->exists());
    }

    public function test_program_head_drop_keeps_the_record_and_re_enrolling_restores_it(): void
    {
        $programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
        $enrollment  = $this->member();

        $this->actingAs($programHead)->delete(route('program-head.sections.drop', [$this->term->section, $enrollment]))->assertRedirect();

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'dropped']);
        $this->assertFalse($this->term->enrollments()->whereKey($enrollment->id)->exists());

        $this->actingAs($programHead)->post(route('program-head.sections.enroll', $this->term->section), ['student_id' => $enrollment->student_id])->assertRedirect();

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'enrolled', 'type' => 'regular']);
    }

    public function test_report_of_grades_prints_dr_for_a_dropped_student(): void
    {
        $enrollment = $this->member();
        $enrollment->update(['status' => Enrollment::STATUS_DROPPED]);
        GradeVerification::create(['section_term_id' => $this->term->id, 'subject_id' => $this->subject->id, 'status' => 'pending']);

        $rows = collect(ReportOfGrades::build($this->term, $this->subject)['pages'])->flatMap(fn ($page) => $page['rows']);
        $row  = $rows->firstWhere('name', $enrollment->student->full_name);

        $this->assertNotNull($row);
        $this->assertSame('Dr', $row['remarks']);
    }
}
