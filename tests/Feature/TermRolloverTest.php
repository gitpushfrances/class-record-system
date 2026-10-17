<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\SectionTerm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermRolloverTest extends TestCase
{
    use RefreshDatabase;

    private SectionTerm $term;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->term    = SectionTerm::where('status', 'active')
            ->whereHas('section.program', fn ($q) => $q->where('code', 'BSCS'))
            ->firstOrFail();
        $this->teacher = User::where('email', 'teacher@classrecord.test')->firstOrFail();
    }

    private function activate(string $semester): void
    {
        AcademicPeriod::query()->update(['is_active' => false]);
        AcademicPeriod::create(['school_year' => $this->term->academic_year, 'semester' => $semester, 'is_active' => true]);
    }

    public function test_same_period_only_changes_the_adviser(): void
    {
        $this->activate($this->term->semester);
        $programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
        $other       = User::factory()->create(['role' => 'teacher', 'status' => 'active', 'department_id' => $programHead->department_id]);

        $this->actingAs($programHead)->post(route('program-head.sections.change-adviser', $this->term->section), ['adviser_id' => $other->id, 'confirmed' => 1])
            ->assertSessionHas('success', 'Term saved successfully.');

        $this->assertDatabaseHas('section_terms', ['id' => $this->term->id, 'status' => 'active', 'adviser_id' => $other->id]);
        $this->assertSame(1, SectionTerm::where('section_id', $this->term->section_id)->count());
    }

    public function test_new_period_closes_the_old_term_and_keeps_its_records(): void
    {
        $students = $this->term->enrollments()->count();
        $this->assertGreaterThan(0, $students);
        $this->activate('2nd Semester');
        $programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();

        $this->actingAs($programHead)->post(route('program-head.sections.change-adviser', $this->term->section), ['adviser_id' => $this->teacher->id, 'confirmed' => 1])
            ->assertSessionHas('success');

        $old = $this->term->fresh();
        $this->assertSame('completed', $old->status);
        $this->assertSame('1st Semester', $old->semester);
        $this->assertSame($students, $old->enrollments()->count());

        $new = SectionTerm::where('section_id', $this->term->section_id)->where('status', 'active')->firstOrFail();
        $this->assertSame('2nd Semester', $new->semester);
        $this->assertSame(0, $new->enrollments()->count());
    }

    public function test_dean_set_term_uses_the_same_rollover(): void
    {
        $this->activate('2nd Semester');
        $dean = User::where('email', 'dean@classrecord.test')->firstOrFail();

        $this->actingAs($dean)->post(route('dean.sections.change-adviser', $this->term->section), ['adviser_id' => $this->teacher->id, 'confirmed' => 1])
            ->assertSessionHas('success');

        $this->assertSame('completed', $this->term->fresh()->status);
        $this->assertSame(2, SectionTerm::where('section_id', $this->term->section_id)->count());
    }
}
