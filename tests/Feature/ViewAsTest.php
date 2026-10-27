<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewAsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->admin = User::where('email', 'admin@classrecord.test')->firstOrFail();
    }

    private function student(string $number, int $programId): Student
    {
        return Student::create([
            'student_number' => $number,
            'first_name'     => 'Vera',
            'last_name'      => 'Viewas',
            'gender'         => 'female',
            'year_level'     => '3rd Year',
            'program_id'     => $programId,
            'student_type'   => 'regular',
            'status'         => 'active',
        ]);
    }

    public function test_admin_must_choose_a_scope_before_opening_dean_or_program_head_pages(): void
    {
        $this->actingAs($this->admin)->get(route('dean.dashboard'))->assertRedirect(route('admin.view-as.index'));
        $this->actingAs($this->admin)->get(route('program-head.sections.index'))->assertRedirect(route('admin.view-as.index'));
        $this->actingAs($this->admin)->get(route('admin.view-as.index'))->assertOk()->assertSee('View as Dean');
    }

    public function test_admin_as_dean_works_only_inside_that_department(): void
    {
        $ccs     = Department::where('code', 'CCS')->firstOrFail();
        $foreign = $this->student('99-0301', Program::where('code', 'BSBA')->firstOrFail()->id);

        $this->actingAs($this->admin)->post(route('admin.view-as.start'), ['role' => 'dean', 'department_id' => $ccs->id])
            ->assertRedirect(route('dean.dashboard'));

        $this->actingAs($this->admin)->get(route('dean.dashboard'))->assertOk();
        $this->actingAs($this->admin)->get(route('dean.students.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('dean.students.index'))->assertDontSee($foreign->student_number);
        $this->actingAs($this->admin)->get(route('program-head.sections.index'))->assertRedirect(route('admin.view-as.index'));
    }

    public function test_admin_as_program_head_can_enroll_in_that_program(): void
    {
        $bscs    = Program::where('code', 'BSCS')->firstOrFail();
        $section = Section::where('program_id', $bscs->id)->firstOrFail();
        $student = $this->student('99-0302', $bscs->id);

        $this->actingAs($this->admin)->post(route('admin.view-as.start'), ['role' => 'program_head', 'program_id' => $bscs->id])
            ->assertRedirect(route('program-head.dashboard'));

        $this->actingAs($this->admin)->post(route('program-head.sections.enroll', $section), ['student_id' => $student->id])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('enrollments', ['student_id' => $student->id, 'status' => 'enrolled']);
    }

    public function test_exit_returns_to_admin(): void
    {
        $ccs = Department::where('code', 'CCS')->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.view-as.start'), ['role' => 'dean', 'department_id' => $ccs->id]);
        $this->actingAs($this->admin)->delete(route('admin.view-as.stop'))->assertRedirect(route('admin.view-as.index'));

        $this->actingAs($this->admin)->get(route('dean.dashboard'))->assertRedirect(route('admin.view-as.index'));
    }

    public function test_only_admin_can_use_view_as(): void
    {
        $dean = User::where('email', 'dean@classrecord.test')->firstOrFail();
        $ccs  = Department::where('code', 'CCS')->firstOrFail();

        $this->actingAs($dean)->post(route('admin.view-as.start'), ['role' => 'dean', 'department_id' => $ccs->id])->assertForbidden();
    }

    public function test_program_head_sees_subjects_requested_by_the_previous_program_head(): void
    {
        $programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
        $subject     = Subject::create([
            'program_id'   => $programHead->program_id,
            'code'         => 'OLD101',
            'name'         => 'Requested By Previous Head',
            'units'        => 3,
            'status'       => 'pending',
            'requested_by' => $this->admin->id,
        ]);

        $this->actingAs($programHead)->get(route('program-head.subjects.index'))->assertOk()->assertSee('OLD101');
        $this->actingAs($programHead)->get(route('program-head.subjects.edit', $subject))->assertOk();
    }
}
