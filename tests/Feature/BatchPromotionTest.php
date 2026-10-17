<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\FinalGrade;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use App\Services\TermRollover;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchPromotionTest extends TestCase
{
    use RefreshDatabase;

    private User $programHead;
    private SectionTerm $oldTerm;
    private Section $from;
    private Section $to;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
        $teacher           = User::where('email', 'teacher@classrecord.test')->firstOrFail();
        $this->oldTerm     = SectionTerm::where('status', 'active')
            ->whereHas('section.program', fn ($q) => $q->where('code', 'BSCS'))
            ->firstOrFail();
        $this->from        = $this->oldTerm->section;
        $this->to          = Section::create([
            'program_id'     => $this->from->program_id,
            'year_number'    => '4',
            'section_letter' => 'A',
            'year_level'     => '4th Year',
            'status'         => 'active',
        ]);

        AcademicPeriod::query()->update(['is_active' => false]);
        $period = AcademicPeriod::create(['school_year' => '2099-2100', 'semester' => '1st Semester', 'is_active' => true]);
        TermRollover::setTerm($this->from, $teacher->id, $period);
        TermRollover::setTerm($this->to, $teacher->id, $period);
    }

    private function targetTerm(Section $section): SectionTerm
    {
        return $section->terms()->where('status', 'active')->firstOrFail();
    }

    public function test_section_page_lists_last_term_students_and_flags_failed_grades(): void
    {
        $member  = $this->oldTerm->enrollments()->with('student')->firstOrFail();
        $subject = Subject::firstOrFail();
        FinalGrade::create(['enrollment_id' => $member->id, 'subject_id' => $subject->id, 'remarks' => 'failed', 'computed_by' => $this->programHead->id]);

        $this->actingAs($this->programHead)->get(route('program-head.promotions.section', $this->from))
            ->assertOk()
            ->assertSee($member->student->student_number)
            ->assertSee("Failed: {$subject->code}")
            ->assertSee('4-A · 4th Year');
    }

    public function test_promoting_enrolls_only_ticked_students_updates_year_level_and_logs_it(): void
    {
        $members = $this->oldTerm->enrollments()->get();
        $picked  = $members->take(2)->pluck('student_id')->all();
        $target  = $this->targetTerm($this->to);

        $this->actingAs($this->programHead)->post(route('program-head.promotions.store', $this->from), ['target_term_id' => $target->id, 'student_ids' => $picked])
            ->assertRedirect(route('program-head.promotions.section', $this->from))
            ->assertSessionHas('success', '2 students moved to BSCS 4-A (4th Year).');

        $this->assertSame(2, $target->enrollments()->count());
        foreach ($picked as $id) {
            $this->assertDatabaseHas('students', ['id' => $id, 'year_level' => '4th Year']);
        }
        $this->assertDatabaseHas('promotions', ['source_section_term_id' => $this->oldTerm->id, 'target_section_term_id' => $target->id, 'year_level' => '4th Year']);
        $this->assertSame($members->count(), $this->oldTerm->enrollments()->count());
    }

    public function test_same_section_carries_students_into_the_next_term_and_skips_duplicates(): void
    {
        $target = $this->targetTerm($this->from);
        $first  = $this->oldTerm->enrollments()->value('student_id');

        $this->actingAs($this->programHead)->post(route('program-head.promotions.store', $this->from), ['target_term_id' => $target->id, 'student_ids' => [$first]]);
        $this->actingAs($this->programHead)->post(route('program-head.promotions.store', $this->from), ['target_term_id' => $target->id, 'student_ids' => [$first]])
            ->assertSessionHas('success', fn ($m) => str_starts_with($m, '0 students moved') && str_contains($m, 'skipped'));

        $this->assertSame(1, $target->enrollments()->count());
    }

    public function test_nothing_moves_without_ticked_students_or_a_valid_target(): void
    {
        $target = $this->targetTerm($this->to);

        $this->actingAs($this->programHead)->post(route('program-head.promotions.store', $this->from), ['target_term_id' => $target->id, 'student_ids' => []])
            ->assertSessionHasErrors('student_ids');
        $this->actingAs($this->programHead)->post(route('program-head.promotions.store', $this->from), ['target_term_id' => $this->oldTerm->id, 'student_ids' => [1]])
            ->assertStatus(422);

        $this->assertSame(0, $target->enrollments()->count());
    }

    public function test_scope_dean_sees_department_programs_and_other_program_head_is_refused(): void
    {
        $dean  = User::where('email', 'dean@classrecord.test')->firstOrFail();
        $other = User::where('email', 'programhead.cba@classrecord.test')->firstOrFail();

        $this->actingAs($dean)->get(route('dean.promotions.index'))->assertOk()->assertSee('BSCS');
        $this->actingAs($dean)->get(route('dean.promotions.section', $this->from))->assertOk();
        $this->actingAs($this->programHead)->get(route('program-head.promotions.index'))->assertRedirect(route('program-head.promotions.program', $this->programHead->program_id));
        $this->actingAs($other)->get(route('program-head.promotions.section', $this->from))->assertForbidden();
    }
}
