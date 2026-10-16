<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SectionEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private User $programHead;
    private SectionTerm $term;
    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
        $this->term        = SectionTerm::where('status', 'active')
            ->whereHas('section.program', fn ($q) => $q->where('code', 'BSCS'))
            ->firstOrFail();
        $this->section     = $this->term->section;
    }

    private function newStudent(?int $programId = null): Student
    {
        return Student::create([
            'student_number' => '99-0201',
            'first_name'     => 'Nina',
            'last_name'      => 'Newcomer',
            'gender'         => 'female',
            'year_level'     => '3rd Year',
            'program_id'     => $programId ?? $this->section->program_id,
            'student_type'   => 'regular',
            'status'         => 'active',
        ]);
    }

    public function test_program_head_sees_and_enrolls_a_program_student(): void
    {
        $student = $this->newStudent();

        $this->actingAs($this->programHead)->get(route('program-head.sections.show', $this->section))
            ->assertOk()->assertSee('99-0201');

        $this->actingAs($this->programHead)->post(route('program-head.sections.enroll', $this->section), ['student_id' => $student->id])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertTrue($this->term->enrollments()->where('student_id', $student->id)->exists());
    }

    public function test_program_head_drop_keeps_the_record_and_re_enrolling_restores_it(): void
    {
        $enrollment = $this->term->enrollments()->firstOrFail();

        $this->actingAs($this->programHead)->delete(route('program-head.sections.drop', [$this->section, $enrollment]))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'dropped']);

        $this->actingAs($this->programHead)->post(route('program-head.sections.enroll', $this->section), ['student_id' => $enrollment->student_id])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'enrolled', 'type' => 'regular']);
    }

    public function test_student_from_another_program_is_refused(): void
    {
        $other   = Program::where('id', '!=', $this->section->program_id)->firstOrFail();
        $student = $this->newStudent($other->id);

        $this->actingAs($this->programHead)->post(route('program-head.sections.enroll', $this->section), ['student_id' => $student->id])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseMissing('enrollments', ['student_id' => $student->id]);
    }

    public function test_other_program_head_cannot_change_the_section(): void
    {
        $other = User::where('email', 'programhead.cba@classrecord.test')->firstOrFail();

        $this->actingAs($other)->post(route('program-head.sections.enroll', $this->section), ['student_id' => $this->newStudent()->id])
            ->assertForbidden();
    }

    public function test_teacher_section_level_enrollment_is_retired(): void
    {
        $this->assertFalse(Route::has('teacher.classes.enroll'));
        $this->assertFalse(Route::has('teacher.classes.unenroll'));

        $teacher = User::where('email', 'teacher@classrecord.test')->firstOrFail();
        $this->actingAs($teacher)->get(route('teacher.classes.show', $this->section))
            ->assertOk()->assertDontSee('Add Student to Class');
    }
}
