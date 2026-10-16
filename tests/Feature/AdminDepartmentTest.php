<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDepartmentTest extends TestCase
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

    public function test_admin_creates_and_renames_a_department(): void
    {
        $this->actingAs($this->admin)->post(route('admin.departments.store'), ['code' => 'cas', 'name' => 'College of Arts and Sciences'])
            ->assertRedirect();

        $department = Department::where('code', 'CAS')->firstOrFail();
        $this->assertSame('active', $department->status);

        $this->actingAs($this->admin)->put(route('admin.departments.update', $department), ['code' => 'CAS', 'name' => 'College of Arts & Sciences'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'College of Arts & Sciences']);
    }

    public function test_duplicate_department_code_is_refused(): void
    {
        $this->actingAs($this->admin)->post(route('admin.departments.store'), ['code' => 'ccs', 'name' => 'Another Computing College'])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, Department::where('code', 'CCS')->count());
    }

    public function test_admin_adds_and_edits_a_program(): void
    {
        $ccs = Department::where('code', 'CCS')->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.programs.store', $ccs), ['code' => 'BSIS', 'name' => 'BS Information Systems'])
            ->assertSessionHas('success');

        $program = Program::where('code', 'BSIS')->firstOrFail();
        $this->assertSame($ccs->id, $program->department_id);
        $this->assertSame('approved', $program->status);

        $this->actingAs($this->admin)->put(route('admin.programs.update', [$ccs, $program]), ['code' => 'BSIS', 'name' => 'BS in Information Systems'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('programs', ['id' => $program->id, 'name' => 'BS in Information Systems']);

        $this->actingAs($this->admin)->post(route('admin.programs.store', $ccs), ['code' => 'BSCS', 'name' => 'Duplicate'])
            ->assertSessionHasErrors('code');
    }

    public function test_only_admin_manages_departments(): void
    {
        $dean = User::where('email', 'dean@classrecord.test')->firstOrFail();

        $this->actingAs($dean)->post(route('admin.departments.store'), ['code' => 'XYZ', 'name' => 'Not Allowed'])->assertForbidden();
        $this->assertDatabaseMissing('departments', ['code' => 'XYZ']);
    }
}
