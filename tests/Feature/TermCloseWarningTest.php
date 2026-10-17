<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\GradeVerification;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermCloseWarningTest extends TestCase
{
    use RefreshDatabase;

    private SectionTerm $term;
    private Subject $subject;
    private User $teacher;
    private User $programHead;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->term        = SectionTerm::where('status', 'active')
            ->whereHas('section.program', fn ($q) => $q->where('code', 'BSCS'))
            ->firstOrFail();
        $this->subject     = Subject::firstOrFail();
        $this->teacher     = User::where('email', 'teacher@classrecord.test')->firstOrFail();
        $this->programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
        $this->term->subjects()->attach($this->subject->id, ['teacher_id' => $this->teacher->id]);

        AcademicPeriod::query()->update(['is_active' => false]);
        AcademicPeriod::create(['school_year' => $this->term->academic_year, 'semester' => '2nd Semester', 'is_active' => true]);
    }

    private function setTerm(array $extra = [])
    {
        return $this->actingAs($this->programHead)
            ->post(route('program-head.sections.change-adviser', $this->term->section), ['adviser_id' => $this->teacher->id, 'confirmed' => 1] + $extra);
    }

    public function test_unverified_subject_stops_set_term_with_a_warning(): void
    {
        $this->setTerm()->assertRedirect()
            ->assertSessionHas('term_close_warning', fn ($w) => $w['subjects'] === ["{$this->subject->code} (not submitted)"]);

        $this->assertSame('active', $this->term->fresh()->status);
        $this->assertSame(1, SectionTerm::where('section_id', $this->term->section_id)->count());
    }

    public function test_confirmed_close_starts_the_new_term(): void
    {
        $this->setTerm(['close_confirmed' => 1])->assertSessionHas('success');

        $this->assertSame('completed', $this->term->fresh()->status);
    }

    public function test_verified_subjects_close_without_a_warning(): void
    {
        GradeVerification::create(['section_term_id' => $this->term->id, 'subject_id' => $this->subject->id, 'status' => 'verified']);

        $this->setTerm()->assertSessionMissing('term_close_warning')->assertSessionHas('success');
        $this->assertSame('completed', $this->term->fresh()->status);
    }

    public function test_dean_gets_the_same_warning_and_both_pages_show_the_dialog(): void
    {
        $dean = User::where('email', 'dean@classrecord.test')->firstOrFail();

        $this->actingAs($dean)->post(route('dean.sections.change-adviser', $this->term->section), ['adviser_id' => $this->teacher->id, 'confirmed' => 1])
            ->assertSessionHas('term_close_warning');
        $this->assertSame('active', $this->term->fresh()->status);

        $warning = ['section_id' => $this->term->section_id, 'adviser_id' => $this->teacher->id, 'subjects' => ['CS101 (not submitted)']];
        $this->actingAs($dean)->withSession(['term_close_warning' => $warning])->get(route('dean.sections.index'))
            ->assertOk()->assertSee('Close Term Anyway');
        $this->actingAs($this->programHead)->withSession(['term_close_warning' => $warning])->get(route('program-head.sections.index'))
            ->assertOk()->assertSee('Close Term Anyway');
    }
}
