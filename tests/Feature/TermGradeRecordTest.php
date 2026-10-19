<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\Enrollment;
use App\Models\GradeConfiguration;
use App\Models\GradeItem;
use App\Models\SectionTerm;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\User;
use App\Services\TermRollover;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TermGradeRecordTest extends TestCase
{
    use RefreshDatabase;

    private const COMPONENTS = [
        ['key' => 'quiz', 'label' => 'Quiz', 'weight' => 60, 'period' => 'midterm'],
        ['key' => 'exam', 'label' => 'Exam', 'weight' => 40, 'period' => 'midterm'],
        ['key' => 'exam_final', 'label' => 'Final Exam', 'weight' => 100, 'period' => 'final'],
    ];

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
    }

    /** First term graded: configuration, the midterm exam and one score. */
    private function gradeFirstTerm(): GradeItem
    {
        GradeConfiguration::create([
            'section_id' => $this->term->section_id, 'section_term_id' => $this->term->id, 'subject_id' => $this->subject->id,
            'computation_method' => 'standard', 'status' => 'active', 'config_json' => self::COMPONENTS,
        ]);

        $exam = GradeItem::create([
            'section_id' => $this->term->section_id, 'section_term_id' => $this->term->id, 'subject_id' => $this->subject->id,
            'component_type' => 'exam', 'period' => 'midterm', 'name' => 'Midterm Examination', 'max_score' => 50, 'created_by' => $this->teacher->id,
        ]);

        StudentGrade::create([
            'enrollment_id' => $this->term->enrollments()->firstOrFail()->id, 'grade_item_id' => $exam->id, 'score' => 45, 'recorded_by' => $this->teacher->id,
        ]);

        return $exam;
    }

    /** Set Term in a new period (the old term is closed) with the same subject and teacher, and one student. */
    private function nextTerm(): SectionTerm
    {
        AcademicPeriod::query()->update(['is_active' => false]);
        $period = AcademicPeriod::create(['school_year' => '2099-2100', 'semester' => $this->term->semester, 'is_active' => true]);

        [$next] = TermRollover::setTerm($this->term->section, $this->teacher->id, $period);
        $next->subjects()->attach($this->subject->id, ['teacher_id' => $this->teacher->id]);

        Enrollment::create([
            'student_id' => $this->term->enrollments()->firstOrFail()->student_id, 'section_term_id' => $next->id,
            'type' => Enrollment::TYPE_REGULAR, 'status' => Enrollment::STATUS_ENROLLED, 'enrolled_at' => now(),
        ]);

        return $next;
    }

    public function test_a_new_term_starts_with_an_empty_class_record(): void
    {
        $this->gradeFirstTerm();
        $next    = $this->nextTerm();
        $section = $this->term->section;

        $this->actingAs($this->teacher)->get(route('teacher.classes.record', [$section, $this->subject]))
            ->assertRedirect(route('teacher.grades.config', [$section, $this->subject, 'from' => 'teaching']));

        $this->actingAs($this->teacher)->post(route('teacher.grades.config.store', [$section, $this->subject]), [
            'components' => self::COMPONENTS, 'computation_method' => 'formula_a',
        ])->assertSessionHasNoErrors();

        // The previous term keeps its own configuration.
        $this->assertSame('standard', $this->term->gradeConfigurationFor($this->subject->id)->computation_method);
        $this->assertSame('formula_a', $next->gradeConfigurationFor($this->subject->id)->computation_method);

        // Before, the old "Midterm Examination" blocked a new one.
        $this->actingAs($this->teacher)->post(route('teacher.grades.items.store', [$section, $this->subject]), [
            'component_type' => 'exam', 'max_score' => 60,
        ])->assertSessionHas('success');

        $this->assertSame(['Midterm Examination'], $next->gradeItemsFor($this->subject->id)->pluck('name')->all());
        $this->assertSame(1, $this->term->gradeItemsFor($this->subject->id)->count());

        $this->actingAs($this->teacher)->get(route('teacher.classes.record', [$section, $this->subject]))
            ->assertOk()->assertSee('/60');
    }

    public function test_an_item_of_a_closed_term_cannot_be_scored(): void
    {
        $oldExam = $this->gradeFirstTerm();
        $next    = $this->nextTerm();
        $section = $this->term->section;
        $student = $next->enrollments()->firstOrFail();

        GradeConfiguration::create([
            'section_id' => $section->id, 'section_term_id' => $next->id, 'subject_id' => $this->subject->id,
            'computation_method' => 'standard', 'status' => 'active', 'config_json' => self::COMPONENTS,
        ]);
        $newExam = GradeItem::create([
            'section_id' => $section->id, 'section_term_id' => $next->id, 'subject_id' => $this->subject->id,
            'component_type' => 'exam', 'period' => 'midterm', 'name' => 'Midterm Examination', 'max_score' => 50, 'created_by' => $this->teacher->id,
        ]);

        $this->actingAs($this->teacher)->postJson(route('teacher.grades.score.cell', [$section, $this->subject, $oldExam]), [
            'enrollment_id' => $student->id, 'score' => 40,
        ])->assertForbidden();

        $this->actingAs($this->teacher)->postJson(route('teacher.grades.score.cell', [$section, $this->subject, $newExam]), [
            'enrollment_id' => $student->id, 'score' => 40,
        ])->assertOk();

        $this->assertDatabaseHas('student_grades', ['enrollment_id' => $student->id, 'grade_item_id' => $newExam->id]);
        $this->assertDatabaseMissing('student_grades', ['enrollment_id' => $student->id, 'grade_item_id' => $oldExam->id]);
    }

    public function test_backfill_assigns_existing_items_and_configurations_to_their_terms(): void
    {
        $first   = $this->term;
        $first->forceFill(['created_at' => now()->subYear()])->save();
        $next    = $this->nextTerm();
        $section = $first->section;
        $a       = $first->enrollments()->firstOrFail();
        $b       = $next->enrollments()->firstOrFail();
        $now     = now();

        // Rows as they were before the migration: no term.
        $row = ['section_id' => $section->id, 'section_term_id' => null, 'subject_id' => $this->subject->id, 'period' => 'midterm', 'max_score' => 10, 'created_by' => $this->teacher->id, 'updated_at' => $now];
        $sharedQuiz = DB::table('grade_items')->insertGetId($row + ['component_type' => 'quiz', 'name' => 'Quiz 1', 'created_at' => now()->subMonths(6)]);
        $unscored   = DB::table('grade_items')->insertGetId($row + ['component_type' => 'quiz', 'name' => 'Quiz 2', 'created_at' => $now->copy()->addMinute()]);
        DB::table('grade_configurations')->insert([
            'section_id' => $section->id, 'section_term_id' => null, 'subject_id' => $this->subject->id,
            'config_json' => json_encode(self::COMPONENTS), 'computation_method' => 'formula_b', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $scoreA = StudentGrade::create(['enrollment_id' => $a->id, 'grade_item_id' => $sharedQuiz, 'score' => 8, 'recorded_by' => $this->teacher->id]);
        $scoreB = StudentGrade::create(['enrollment_id' => $b->id, 'grade_item_id' => $sharedQuiz, 'score' => 6, 'recorded_by' => $this->teacher->id]);

        (require database_path('migrations/2026_10_20_000100_scope_grade_records_to_section_terms.php'))->backfill();

        // The item scored in both terms is split: one copy per term, each term's score on its own copy.
        $this->assertSame($first->id, (int) DB::table('grade_items')->where('id', $sharedQuiz)->value('section_term_id'));
        $copy = GradeItem::where('section_term_id', $next->id)->where('name', 'Quiz 1')->firstOrFail();
        $this->assertSame($sharedQuiz, (int) $scoreA->fresh()->grade_item_id);
        $this->assertSame($copy->id, (int) $scoreB->fresh()->grade_item_id);

        // An item without scores goes to the term that was current when it was created.
        $this->assertSame($next->id, (int) DB::table('grade_items')->where('id', $unscored)->value('section_term_id'));

        // The configuration is kept by both terms that used it.
        $this->assertSame('formula_b', $first->gradeConfigurationFor($this->subject->id)?->computation_method);
        $this->assertSame('formula_b', $next->gradeConfigurationFor($this->subject->id)?->computation_method);
        $this->assertSame(0, GradeConfiguration::whereNull('section_term_id')->count());
    }
}
