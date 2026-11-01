<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\FacultyBorrowRequest;
use App\Models\SectionTerm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** An adviser is an active teacher of the Dean's or Program Head's own department. The server checks it, not just the dropdown. */
class AdviserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private SectionTerm $term;
    private ?int $adviserBefore;
    private User $dean;
    private User $programHead;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->term = SectionTerm::where('status', 'active')
            ->whereHas('section.program', fn ($q) => $q->where('code', 'BSCS'))
            ->firstOrFail();
        $this->adviserBefore = $this->term->adviser_id;

        AcademicPeriod::query()->update(['is_active' => false]);
        AcademicPeriod::create(['school_year' => $this->term->academic_year, 'semester' => $this->term->semester, 'is_active' => true]);

        $this->dean        = User::where('email', 'dean@classrecord.test')->firstOrFail();
        $this->programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
    }

    private function setAdviser(User $by, string $route, int $adviserId)
    {
        return $this->actingAs($by)->post(route($route, $this->term->section), ['adviser_id' => $adviserId, 'confirmed' => 1]);
    }

    private function assertAdviserUnchanged(): void
    {
        $this->assertSame($this->adviserBefore, $this->term->fresh()->adviser_id);
    }

    public function test_a_teacher_of_the_same_department_can_be_adviser_for_both_roles(): void
    {
        $teacher = User::where('email', 'teacher@classrecord.test')->firstOrFail();

        $this->setAdviser($this->dean, 'dean.sections.change-adviser', $teacher->id)->assertSessionHas('success');
        $this->assertSame($teacher->id, $this->term->fresh()->adviser_id);

        $other = User::factory()->create(['role' => 'teacher', 'status' => 'active', 'department_id' => $this->programHead->department_id]);
        $this->setAdviser($this->programHead, 'program-head.sections.change-adviser', $other->id)->assertSessionHas('success');
        $this->assertSame($other->id, $this->term->fresh()->adviser_id);
    }

    public function test_a_teacher_of_another_department_cannot_be_adviser(): void
    {
        $foreign = User::where('email', 'teacher.cba@classrecord.test')->firstOrFail();

        $this->setAdviser($this->dean, 'dean.sections.change-adviser', $foreign->id)->assertSessionHas('error');
        $this->setAdviser($this->programHead, 'program-head.sections.change-adviser', $foreign->id)->assertSessionHas('error');

        $this->assertAdviserUnchanged();
    }

    public function test_a_borrowed_teacher_cannot_be_adviser_even_with_a_crafted_request(): void
    {
        $borrowed = User::where('email', 'teacher.cba@classrecord.test')->firstOrFail();
        FacultyBorrowRequest::create([
            'borrowing_department_id' => $this->dean->department_id,
            'lending_department_id'   => $borrowed->department_id,
            'teacher_id'              => $borrowed->id,
            'academic_year'           => $this->term->academic_year,
            'semester'                => $this->term->semester,
            'status'                  => 'approved',
            'requested_by'            => $this->dean->id,
        ]);

        $this->setAdviser($this->dean, 'dean.sections.change-adviser', $borrowed->id)->assertSessionHas('error');
        $this->setAdviser($this->programHead, 'program-head.sections.change-adviser', $borrowed->id)->assertSessionHas('error');

        $this->assertAdviserUnchanged();
    }

    public function test_an_inactive_teacher_or_a_non_teacher_cannot_be_adviser(): void
    {
        $inactive = User::factory()->create(['role' => 'teacher', 'status' => 'inactive', 'department_id' => $this->dean->department_id]);

        $this->setAdviser($this->dean, 'dean.sections.change-adviser', $inactive->id)->assertSessionHas('error');
        $this->setAdviser($this->programHead, 'program-head.sections.change-adviser', $this->dean->id)->assertSessionHas('error');

        $this->assertAdviserUnchanged();
    }

    public function test_the_error_names_the_rule(): void
    {
        $foreign = User::where('email', 'teacher.cba@classrecord.test')->firstOrFail();

        $this->setAdviser($this->dean, 'dean.sections.change-adviser', $foreign->id)
            ->assertSessionHas('error', 'The adviser must be an active teacher of your department. Borrowed teachers cannot be advisers.');
    }
}
