<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\GradeVerification;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectRosterTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private SectionTerm $term;
    private Section $section;
    private Subject $subject;

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
        $this->section = $this->term->section;
        $this->subject = Subject::firstOrFail();
        $this->term->subjects()->attach($this->subject->id, ['teacher_id' => $this->teacher->id]);
    }

    private function url(string $name, array $extra = []): string
    {
        return route($name, array_merge([$this->section, $this->subject], $extra));
    }

    private function outsider(): Student
    {
        return Student::create([
            'student_number' => '99-0101',
            'first_name'     => 'Rita',
            'last_name'      => 'Retake',
            'gender'         => 'female',
            'year_level'     => '4th Year',
            'student_type'   => 'irregular',
            'status'         => 'active',
        ]);
    }

    public function test_search_finds_students_outside_the_class_only(): void
    {
        $this->outsider();
        $member = $this->term->enrollments()->with('student')->firstOrFail()->student;

        $this->actingAs($this->teacher)->get($this->url('teacher.classes.students', ['q' => 'Retake']))
            ->assertOk()->assertSee('99-0101');

        $this->actingAs($this->teacher)->get($this->url('teacher.classes.students', ['q' => $member->student_number]))
            ->assertOk()->assertDontSee('fa-solid fa-plus', false);
    }

    public function test_teacher_adds_an_outside_student_to_this_subject_only(): void
    {
        $student = $this->outsider();
        $other   = Subject::where('id', '!=', $this->subject->id)->firstOrFail();

        $this->actingAs($this->teacher)->post($this->url('teacher.classes.students.store'), ['student_id' => $student->id])
            ->assertRedirect()->assertSessionHas('success');

        $enrollment = Enrollment::where('student_id', $student->id)->where('section_term_id', $this->term->id)->firstOrFail();
        $this->assertSame(Enrollment::TYPE_IRREGULAR, $enrollment->type);
        $this->assertTrue($this->term->rosterFor($this->subject->id)->whereKey($enrollment->id)->exists());
        $this->assertFalse($this->term->rosterFor($other->id)->whereKey($enrollment->id)->exists());
        $this->assertFalse($this->term->enrollments()->whereKey($enrollment->id)->exists());
    }

    public function test_teacher_drops_and_restores_a_student_in_this_subject(): void
    {
        $enrollment = $this->term->enrollments()->firstOrFail();

        $this->actingAs($this->teacher)->delete($this->url('teacher.classes.students.destroy', [$enrollment]))->assertRedirect();
        $this->assertFalse($this->term->rosterFor($this->subject->id)->whereKey($enrollment->id)->exists());
        $this->assertTrue($this->term->droppedFrom($this->subject->id)->whereKey($enrollment->id)->exists());
        $this->assertTrue($this->term->enrollments()->whereKey($enrollment->id)->exists());

        $this->actingAs($this->teacher)->patch($this->url('teacher.classes.students.restore', [$enrollment]))->assertRedirect();
        $this->assertTrue($this->term->rosterFor($this->subject->id)->whereKey($enrollment->id)->exists());
    }

    public function test_class_list_is_locked_after_submission(): void
    {
        $enrollment = $this->term->enrollments()->firstOrFail();
        GradeVerification::create(['section_term_id' => $this->term->id, 'subject_id' => $this->subject->id, 'status' => 'pending']);

        $this->actingAs($this->teacher)->delete($this->url('teacher.classes.students.destroy', [$enrollment]))->assertForbidden();
        $this->actingAs($this->teacher)->post($this->url('teacher.classes.students.store'), ['student_id' => $this->outsider()->id])->assertForbidden();
        $this->assertTrue($this->term->rosterFor($this->subject->id)->whereKey($enrollment->id)->exists());
    }

    public function test_only_the_subject_teacher_can_change_the_class_list(): void
    {
        $other = User::factory()->create(['role' => 'teacher', 'status' => 'active']);

        $this->actingAs($other)->get($this->url('teacher.classes.students'))->assertForbidden();
        $this->actingAs($other)->post($this->url('teacher.classes.students.store'), ['student_id' => $this->outsider()->id])->assertForbidden();
    }
}
