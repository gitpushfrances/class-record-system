<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectPlacementTest extends TestCase
{
    use RefreshDatabase;

    private User $programHead;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
    }

    private function subject(string $code, ?string $year, ?string $semester, string $status = 'approved'): Subject
    {
        return Subject::create([
            'program_id'   => $this->programHead->program_id,
            'code'         => $code,
            'name'         => "Subject {$code}",
            'units'        => 3,
            'year_level'   => $year,
            'semester'     => $semester,
            'status'       => $status,
            'requested_by' => $this->programHead->id,
        ]);
    }

    public function test_subject_request_requires_year_level_and_semester(): void
    {
        $this->actingAs($this->programHead)->post(route('program-head.subjects.store'), ['code' => 'NEW1', 'name' => 'New One', 'units' => 3])
            ->assertSessionHasErrors(['year_level', 'semester']);

        $this->actingAs($this->programHead)->post(route('program-head.subjects.store'), [
            'code' => 'NEW1', 'name' => 'New One', 'units' => 3, 'year_level' => '2nd Year', 'semester' => '1st Semester',
        ])->assertRedirect(route('program-head.subjects.index'));

        $this->assertDatabaseHas('subjects', ['code' => 'NEW1', 'year_level' => '2nd Year', 'semester' => '1st Semester']);
    }

    public function test_program_head_sets_placement_on_an_approved_subject(): void
    {
        $subject = $this->subject('OLD1', null, null);

        $this->actingAs($this->programHead)->patch(route('program-head.subjects.placement', $subject), ['year_level' => '1st Year', 'semester' => 'Summer'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'year_level' => '1st Year', 'semester' => 'Summer']);
    }

    public function test_subjects_are_grouped_in_curriculum_order_with_unspecified_last(): void
    {
        $this->subject('B2', '2nd Year', '1st Semester');
        $this->subject('A1', '1st Year', '2nd Semester');
        $this->subject('X0', null, null);
        $this->subject('A0', '1st Year', '1st Semester');

        $groups = Subject::byPlacement(Subject::where('program_id', $this->programHead->program_id)->get())->keys()->all();

        $this->assertSame(['1st Year · 1st Semester', '1st Year · 2nd Semester', '2nd Year · 1st Semester', Subject::UNSPECIFIED], $groups);

        $this->actingAs($this->programHead)->get(route('program-head.subjects.index'))
            ->assertOk()
            ->assertSeeInOrder(['1st Year · 1st Semester', 'A0', '1st Year · 2nd Semester', 'A1', '2nd Year · 1st Semester', 'B2', Subject::UNSPECIFIED, 'X0']);
    }

    public function test_dean_and_admin_see_the_placement_groups(): void
    {
        $this->subject('D1', '3rd Year', '2nd Semester');
        $dean  = User::where('email', 'dean@classrecord.test')->firstOrFail();
        $admin = User::where('email', 'admin@classrecord.test')->firstOrFail();

        $this->actingAs($dean)->get(route('dean.subjects.index'))->assertOk()->assertSeeInOrder(['BSCS', '3rd Year · 2nd Semester', 'D1']);
        $this->actingAs($admin)->get(route('admin.subjects.index'))->assertOk()->assertSeeInOrder(['College of Computer Studies', '3rd Year · 2nd Semester', 'D1']);
    }
}
