<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\SectionTerm;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Panel recommendation: the Program Head enrolls and imports students; the Dean only views them.
 */
class DeanReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    private User $dean;
    private SectionTerm $term;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->dean = User::where('email', 'dean@classrecord.test')->firstOrFail();
        $this->term = SectionTerm::where('status', 'active')
            ->whereHas('section.program', fn ($q) => $q->where('code', 'BSCS'))
            ->firstOrFail();
    }

    public function test_dean_has_no_student_or_enrollment_write_routes(): void
    {
        foreach (['students.create', 'students.store', 'students.edit', 'students.update', 'students.destroy',
                  'students.import', 'students.template',
                  'enrollments.store', 'enrollments.destroy', 'enrollments.remove'] as $name) {
            $this->assertFalse(Route::has('dean.' . $name), "dean.{$name} should not exist");
        }
    }

    public function test_dean_can_still_view_students_and_class_lists(): void
    {
        $this->actingAs($this->dean)->get(route('dean.students.index'))
            ->assertOk()
            ->assertDontSee('Add Student')
            ->assertDontSee('importStudentsDialog', false);

        $this->actingAs($this->dean)->get(route('dean.enrollments.index'))->assertOk();
        $this->actingAs($this->dean)->get(route('dean.enrollments.show', $this->term))->assertOk();
    }

    public function test_dean_requests_to_the_old_write_urls_change_nothing(): void
    {
        $enrollment = $this->term->enrollments()->firstOrFail();
        $student    = $enrollment->student;
        $before     = [Student::count(), Enrollment::count()];

        $this->actingAs($this->dean)->post('/dean/students', ['first_name' => 'X'])->assertStatus(405);
        $this->actingAs($this->dean)->put('/dean/students/' . $student->id, ['first_name' => 'X'])->assertNotFound();
        $this->actingAs($this->dean)->delete('/dean/students/' . $student->id)->assertNotFound();
        $this->actingAs($this->dean)->get('/dean/students/create')->assertNotFound();
        $this->actingAs($this->dean)->post('/dean/section-terms/' . $this->term->id . '/enrollments', ['student_id' => $student->id])->assertStatus(405);
        $this->actingAs($this->dean)->delete('/dean/section-terms/' . $this->term->id . '/enrollments/' . $enrollment->id)->assertNotFound();

        $this->assertSame($before, [Student::count(), Enrollment::count()]);
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'enrolled']);
    }
}
