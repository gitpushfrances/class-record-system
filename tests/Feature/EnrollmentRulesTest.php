<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\GradeVerification;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $programHead;
    private User $teacher;
    private SectionTerm $term;
    private Section $other;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
        $this->teacher     = User::where('email', 'teacher@classrecord.test')->firstOrFail();
        $this->term        = SectionTerm::where('status', 'active')
            ->whereHas('section.program', fn ($q) => $q->where('code', 'BSCS'))
            ->firstOrFail();

        // A second BSCS section in the same semester.
        $this->other = Section::create([
            'program_id'     => $this->term->section->program_id,
            'year_number'    => '3',
            'section_letter' => 'B',
            'year_level'     => '3rd Year',
            'status'         => 'active',
        ]);
        SectionTerm::create([
            'section_id'    => $this->other->id,
            'adviser_id'    => $this->teacher->id,
            'academic_year' => $this->term->academic_year,
            'semester'      => $this->term->semester,
            'status'        => 'active',
        ]);
    }

    private function newStudent(string $number = '99-0401'): Student
    {
        return Student::create([
            'student_number' => $number,
            'first_name'     => 'Nora',
            'last_name'      => 'Newcomer',
            'gender'         => 'female',
            'year_level'     => '3rd Year',
            'program_id'     => $this->term->section->program_id,
            'student_type'   => 'regular',
            'status'         => 'active',
        ]);
    }

    private function enroll(Section $section, Student $student)
    {
        return $this->actingAs($this->programHead)->post(route('program-head.sections.enroll', $section), ['student_id' => $student->id]);
    }

    public function test_a_student_is_in_only_one_section_per_semester(): void
    {
        $member = $this->term->enrollments()->with('student')->firstOrFail();

        $this->enroll($this->other, $member->student)
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'already enrolled in BSCS 3-A'));

        $this->assertDatabaseMissing('enrollments', ['student_id' => $member->student_id, 'section_term_id' => $this->other->terms()->value('id')]);
    }

    public function test_search_list_offers_only_students_without_a_section_this_semester(): void
    {
        $member = $this->term->enrollments()->with('student')->firstOrFail();
        $this->newStudent();

        $this->actingAs($this->programHead)->get(route('program-head.sections.show', $this->other))
            ->assertOk()
            ->assertSee('99-0401')
            ->assertDontSee($member->student->student_number)
            ->assertSee('data-picker-input', false);
    }

    public function test_program_head_removes_a_student_added_by_mistake(): void
    {
        $student = $this->newStudent();
        $this->enroll($this->term->section, $student)->assertSessionHas('success');
        $enrollment = Enrollment::where('student_id', $student->id)->firstOrFail();

        $this->actingAs($this->programHead)->delete(route('program-head.sections.remove', [$this->term->section, $enrollment]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
    }

    public function test_remove_is_refused_once_the_student_has_records(): void
    {
        $student = $this->newStudent();
        $this->enroll($this->term->section, $student)->assertSessionHas('success');
        $enrollment = Enrollment::where('student_id', $student->id)->firstOrFail();
        AttendanceRecord::create([
            'enrollment_id' => $enrollment->id, 'subject_id' => Subject::firstOrFail()->id,
            'date' => now()->toDateString(), 'session_type' => 'lecture', 'status' => 'present', 'recorded_by' => $this->teacher->id,
        ]);

        $this->actingAs($this->programHead)->delete(route('program-head.sections.remove', [$this->term->section, $enrollment]))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Use Drop'));

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'enrolled']);
    }

    public function test_remove_is_refused_while_a_subject_is_submitted(): void
    {
        $student = $this->newStudent();
        $this->enroll($this->term->section, $student)->assertSessionHas('success');
        $enrollment = Enrollment::where('student_id', $student->id)->firstOrFail();
        GradeVerification::create(['section_term_id' => $this->term->id, 'subject_id' => Subject::firstOrFail()->id, 'status' => 'pending']);

        $this->actingAs($this->programHead)->delete(route('program-head.sections.remove', [$this->term->section, $enrollment]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id]);
    }

    public function test_dean_page_has_search_drop_and_remove(): void
    {
        $dean    = User::where('email', 'dean@classrecord.test')->firstOrFail();
        $student = $this->newStudent();
        $this->enroll($this->term->section, $student)->assertSessionHas('success');
        $enrollment = Enrollment::where('student_id', $student->id)->firstOrFail();

        $this->actingAs($dean)->get(route('dean.enrollments.show', $this->term))
            ->assertOk()->assertSee('data-picker-input', false)->assertSee('>Drop<', false)->assertSee('>Remove<', false);

        $this->actingAs($dean)->delete(route('dean.enrollments.remove', [$this->term, $enrollment]))->assertSessionHas('success');
        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
    }

    public function test_teacher_removes_an_irregular_student_added_by_mistake_but_not_a_section_member(): void
    {
        $subject = Subject::firstOrFail();
        $this->term->subjects()->attach($subject->id, ['teacher_id' => $this->teacher->id]);
        $student = $this->newStudent('99-0402');

        $this->actingAs($this->teacher)->post(route('teacher.classes.students.store', [$this->term->section, $subject]), ['student_id' => $student->id])
            ->assertSessionHas('success');
        $irregular = Enrollment::where('student_id', $student->id)->firstOrFail();
        $this->assertSame(Enrollment::TYPE_IRREGULAR, $irregular->type);

        $this->actingAs($this->teacher)->delete(route('teacher.classes.students.remove', [$this->term->section, $subject, $irregular]))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('enrollments', ['id' => $irregular->id]);
        $this->assertDatabaseMissing('subject_enrollments', ['enrollment_id' => $irregular->id]);

        $member = $this->term->enrollments()->firstOrFail();
        $this->actingAs($this->teacher)->delete(route('teacher.classes.students.remove', [$this->term->section, $subject, $member]))
            ->assertNotFound();
        $this->assertDatabaseHas('enrollments', ['id' => $member->id]);
    }
}
