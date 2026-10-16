<?php

namespace Tests\Feature;

use App\Models\FinalGrade;
use App\Models\GradeVerification;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportOfGradesTest extends TestCase
{
    use RefreshDatabase;

    private SectionTerm $term;
    private Subject $subject;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->teacher = User::where('email', 'teacher@classrecord.test')->firstOrFail();
        $this->term    = SectionTerm::where('status', 'active')
            ->whereHas('section.program', fn ($q) => $q->where('code', 'BSCS'))
            ->firstOrFail();
        $this->subject = Subject::firstOrFail();

        $this->term->subjects()->attach($this->subject->id, [
            'teacher_id'     => $this->teacher->id,
            'schedule_days'  => 'M,W',
            'schedule_start' => '10:30',
            'schedule_end'   => '12:00',
        ]);
    }

    private function submit(): string
    {
        $enrollment = $this->term->enrollments()->with('student')->firstOrFail();

        FinalGrade::create([
            'enrollment_id'      => $enrollment->id,
            'subject_id'         => $this->subject->id,
            'midterm_percentage' => 90,
            'midterm_numerical'  => 1.4,
            'final_percentage'   => 85,
            'final_numerical'    => 1.6,
            'average_numerical'  => 1.5,
            'final_grade'        => 87.5,
            'numerical_grade'    => 1.5,
            'letter_grade'       => '1.5',
            'remarks'            => 'passed',
            'is_locked'          => true,
            'computed_by'        => $this->teacher->id,
            'locked_at'          => now(),
        ]);

        GradeVerification::create([
            'section_term_id' => $this->term->id,
            'subject_id'      => $this->subject->id,
            'status'          => 'pending',
        ]);

        return $enrollment->student->full_name;
    }

    private function teacherUrl(): string
    {
        return route('teacher.rog.show', [$this->term->section, $this->subject]);
    }

    public function test_teacher_is_sent_back_until_grades_are_submitted(): void
    {
        $this->actingAs($this->teacher)->get($this->teacherUrl())
            ->assertRedirect(route('teacher.grades.final', [$this->term->section, $this->subject]));
    }

    public function test_teacher_sees_the_report_after_submitting(): void
    {
        $name = $this->submit();

        $this->actingAs($this->teacher)->get($this->teacherUrl())
            ->assertOk()
            ->assertSee('REPORT OF GRADES')
            ->assertSee('First Semester, School Year 2024-2025')
            ->assertSee('10:30-12:00PM (MW)')
            ->assertSee($name)
            ->assertSee('Passed')
            ->assertSee('ESSU-ACAD-712.b | Version 5');
    }

    public function test_program_head_sets_the_schedule_when_assigning(): void
    {
        $programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();

        $this->actingAs($programHead)
            ->post(route('program-head.sections.change-subject-teacher', $this->term->section), [
                'subject_id' => $this->subject->id,
                'teacher_id' => $this->teacher->id,
                'schedule_days'  => ['M', 'W', 'F'],
                'schedule_start' => '08:00',
                'schedule_end'   => '09:00',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('section_subject_teachers', [
            'section_term_id' => $this->term->id,
            'subject_id'      => $this->subject->id,
            'schedule_days'   => 'M,W,F',
        ]);
    }

    public function test_overlapping_schedule_in_the_same_section_is_rejected(): void
    {
        $programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
        $other       = Subject::where('id', '!=', $this->subject->id)->firstOrFail();
        $this->term->subjects()->attach($other->id, ['teacher_id' => $this->teacher->id]);

        $this->actingAs($programHead)
            ->post(route('program-head.sections.change-subject-teacher', $this->term->section), [
                'subject_id'     => $other->id,
                'teacher_id'     => $this->teacher->id,
                'schedule_days'  => ['W'],
                'schedule_start' => '11:00',
                'schedule_end'   => '12:30',
            ])
            ->assertSessionHasErrors('schedule_days');

        $this->assertStringContainsString($this->subject->code, session('errors')->first('schedule_days'));
        $this->assertDatabaseHas('section_subject_teachers', [
            'section_term_id' => $this->term->id,
            'subject_id'      => $other->id,
            'schedule_days'   => null,
        ]);
    }

    public function test_program_head_and_dean_of_the_program_can_view(): void
    {
        $this->submit();

        $programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
        $dean        = User::where('email', 'dean@classrecord.test')->firstOrFail();

        $this->actingAs($programHead)->get(route('program-head.rog.show', [$this->term, $this->subject]))->assertOk();
        $this->actingAs($dean)->get(route('dean.rog.show', [$this->term, $this->subject]))->assertOk();
    }

    public function test_other_department_is_forbidden(): void
    {
        $this->submit();

        $programHead = User::where('email', 'programhead.cba@classrecord.test')->firstOrFail();
        $dean        = User::where('email', 'dean.cba@classrecord.test')->firstOrFail();

        $this->actingAs($programHead)->get(route('program-head.rog.show', [$this->term, $this->subject]))->assertForbidden();
        $this->actingAs($dean)->get(route('dean.rog.show', [$this->term, $this->subject]))->assertForbidden();
    }

    public function test_reviewers_get_not_found_before_submission(): void
    {
        $programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();

        $this->actingAs($programHead)->get(route('program-head.rog.show', [$this->term, $this->subject]))->assertNotFound();
    }
}
