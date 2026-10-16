<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DeanFacultyTest extends TestCase
{
    use RefreshDatabase;

    private User $dean;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->dean = User::where('email', 'dean@classrecord.test')->firstOrFail();
    }

    private function idleTeacher(): User
    {
        return User::factory()->create([
            'name'          => 'Idle Teacher',
            'role'          => 'teacher',
            'status'        => 'active',
            'department_id' => $this->dean->department_id,
        ]);
    }

    public function test_dean_lists_only_their_department_teachers(): void
    {
        $this->actingAs($this->dean)->get(route('dean.faculty.index'))
            ->assertOk()
            ->assertSee('teacher@classrecord.test')
            ->assertDontSee('teacher.cba@classrecord.test');
    }

    public function test_teacher_with_a_load_cannot_be_deactivated(): void
    {
        $busy = User::where('email', 'teacher@classrecord.test')->firstOrFail();

        $this->actingAs($this->dean)->patch(route('dean.faculty.deactivate', $busy))->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $busy->id, 'status' => 'active']);
    }

    public function test_deactivated_teacher_cannot_sign_in_and_can_be_reactivated(): void
    {
        $teacher = $this->idleTeacher();

        $this->actingAs($this->dean)->patch(route('dean.faculty.deactivate', $teacher))->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $teacher->id, 'status' => 'inactive']);
        $this->actingAs($teacher->fresh())->get(route('teacher.dashboard'))->assertRedirect(route('login'));

        $this->actingAs($this->dean)->patch(route('dean.faculty.activate', $teacher))->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $teacher->id, 'status' => 'active']);
    }

    public function test_dean_cannot_change_another_departments_teacher(): void
    {
        $foreign = User::where('email', 'teacher.cba@classrecord.test')->firstOrFail();

        $this->actingAs($this->dean)->patch(route('dean.faculty.deactivate', $foreign))->assertForbidden();
    }

    public function test_dean_only_views_sections(): void
    {
        foreach (['create', 'store', 'edit', 'update', 'destroy'] as $action) {
            $this->assertFalse(Route::has("dean.sections.{$action}"), "dean.sections.{$action} should not exist");
        }

        $this->actingAs($this->dean)->get(route('dean.sections.index'))->assertOk()->assertDontSee('+ Create Section');
    }
}
