<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\GradeConfiguration;
use App\Models\GradeItem;
use App\Models\GradeVerification;
use App\Models\SectionTerm;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\User;
use App\Services\ClassRecordData;
use App\Services\TermRollover;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PastClassesTest extends TestCase
{
    use RefreshDatabase;

    private SectionTerm $term;
    private Subject $subject;
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
        $this->subject = Subject::firstOrFail();
        $this->term->subjects()->attach($this->subject->id, ['teacher_id' => $this->teacher->id]);

        GradeConfiguration::create([
            'section_id' => $this->term->section_id, 'section_term_id' => $this->term->id, 'subject_id' => $this->subject->id,
            'computation_method' => 'standard', 'status' => 'active', 'config_json' => [
                ['key' => 'quiz', 'label' => 'Quiz', 'weight' => 100, 'period' => 'midterm'],
                ['key' => 'exam_final', 'label' => 'Final Exam', 'weight' => 100, 'period' => 'final'],
            ],
        ]);
        $quiz = GradeItem::create([
            'section_id' => $this->term->section_id, 'section_term_id' => $this->term->id, 'subject_id' => $this->subject->id,
            'component_type' => 'quiz', 'period' => 'midterm', 'name' => 'Quiz 1', 'max_score' => 10, 'created_by' => $this->teacher->id,
        ]);
        StudentGrade::create([
            'enrollment_id' => $this->term->enrollments()->firstOrFail()->id, 'grade_item_id' => $quiz->id, 'score' => 9, 'recorded_by' => $this->teacher->id,
        ]);
        GradeVerification::create(['section_term_id' => $this->term->id, 'subject_id' => $this->subject->id, 'status' => 'verified']);
    }

    /** Set Term in a new period: the graded term is closed. */
    private function closeTerm(): void
    {
        AcademicPeriod::query()->update(['is_active' => false]);
        $period = AcademicPeriod::create(['school_year' => '2099-2100', 'semester' => $this->term->semester, 'is_active' => true]);
        TermRollover::setTerm($this->term->section, $this->teacher->id, $period);
    }

    private function showUrl(): string
    {
        return route('teacher.past-classes.show', [$this->term, $this->subject]);
    }

    public function test_a_closed_class_is_listed_and_opens_read_only(): void
    {
        $this->closeTerm();

        $this->actingAs($this->teacher)->get(route('teacher.past-classes.index'))
            ->assertOk()
            ->assertSee($this->subject->code)
            ->assertSee('Verified')
            ->assertSee($this->showUrl(), false);

        $this->actingAs($this->teacher)->get($this->showUrl())
            ->assertOk()
            ->assertSee('This record is read-only.')
            ->assertSee('Back to Past Classes')
            ->assertSee('Quiz 1')
            ->assertSee('90.00%')
            ->assertDontSee('class="score-input"', false)
            ->assertDontSee('id="addItemDialog"', false)
            ->assertDontSee(route('teacher.grades.config', [$this->term->section, $this->subject]), false);

        $this->actingAs($this->teacher)->get(route('teacher.past-classes.rog', [$this->term, $this->subject]))
            ->assertOk()
            ->assertSee('REPORT OF GRADES');
    }

    public function test_only_closed_terms_of_the_teacher_open(): void
    {
        // Still active: it belongs to My Subjects, not Past Classes.
        $this->actingAs($this->teacher)->get($this->showUrl())->assertNotFound();

        $this->closeTerm();

        $other = User::factory()->create(['role' => 'teacher', 'status' => 'active', 'department_id' => $this->teacher->department_id]);

        $this->actingAs($other)->get($this->showUrl())->assertForbidden();
        $this->actingAs($other)->get(route('teacher.past-classes.index'))->assertOk()->assertDontSee($this->subject->code . ' —');
    }

    public function test_a_closed_term_uses_its_own_midterm_cutoff(): void
    {
        AcademicPeriod::updateOrCreate(
            ['school_year' => $this->term->academic_year, 'semester' => $this->term->semester],
            ['midterm_cutoff_date' => '2024-09-30']
        );

        $this->closeTerm();

        $this->assertSame('2024-09-30', ClassRecordData::cutoffFor($this->term->fresh())?->toDateString());
    }
}
