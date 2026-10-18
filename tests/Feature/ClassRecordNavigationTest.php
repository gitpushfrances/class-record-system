<?php

namespace Tests\Feature;

use App\Models\GradeConfiguration;
use App\Models\GradeItem;
use App\Models\SectionTerm;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassRecordNavigationTest extends TestCase
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
            'section_id'         => $this->term->section_id,
            'subject_id'         => $this->subject->id,
            'computation_method' => 'standard',
            'status'             => 'active',
            'config_json'        => [
                ['key' => 'quiz', 'label' => 'Quiz', 'weight' => 100, 'period' => 'midterm'],
                ['key' => 'exam_final', 'label' => 'Final Exam', 'weight' => 100, 'period' => 'final'],
            ],
        ]);

        $quiz = GradeItem::create([
            'section_id' => $this->term->section_id, 'subject_id' => $this->subject->id, 'component_type' => 'quiz',
            'period' => 'midterm', 'name' => 'Quiz 1', 'max_score' => 10, 'date_given' => now()->toDateString(), 'created_by' => $this->teacher->id,
        ]);
        StudentGrade::create([
            'enrollment_id' => $this->term->enrollments()->firstOrFail()->id, 'grade_item_id' => $quiz->id, 'score' => 9, 'recorded_by' => $this->teacher->id,
        ]);
    }

    private function record(array $query = [])
    {
        return $this->actingAs($this->teacher)->get(route('teacher.classes.record', [$this->term->section, $this->subject] + $query));
    }

    public function test_class_record_shows_midterm_and_finals_grade_columns(): void
    {
        $enrollmentId = $this->term->enrollments()->firstOrFail()->id;

        $this->record()->assertOk()
            ->assertSee('Midterm<br>Grade', false)
            ->assertSee('Finals<br>Grade', false)
            ->assertSee('data-calc="r' . $enrollmentId . '-midterm-grade"', false)
            ->assertSee('90.00%')
            ->assertSee('INC');
    }

    public function test_back_button_follows_where_the_class_was_opened_from(): void
    {
        $section = $this->term->section;

        $this->record()->assertSee('Back to My Subjects')->assertSee('href="' . route('teacher.teaching') . '"', false);
        $this->record(['from' => 'dashboard'])->assertSee('Back to Dashboard')->assertSee('href="' . route('teacher.dashboard') . '"', false);
        $this->record(['from' => 'advisory'])->assertSee('Back to Section')
            ->assertSee('href="' . route('teacher.classes.show', [$section, 'from' => 'advisory']) . '"', false);

        // An unknown origin falls back to My Subjects and is never used in a link the page builds.
        $this->record(['from' => 'https://evil.example'])
            ->assertSee('Back to My Subjects')
            ->assertSee(route('teacher.grades.config', [$section, $this->subject, 'from' => 'teaching']), false)
            ->assertDontSee('href="https://evil.example', false);
    }

    public function test_class_pages_keep_the_origin_and_lead_back_to_the_class_record(): void
    {
        $this->record(['from' => 'dashboard'])
            ->assertSee(route('teacher.grades.config', [$this->term->section, $this->subject, 'from' => 'dashboard']), false);

        $this->actingAs($this->teacher)->get(route('teacher.grades.config', [$this->term->section, $this->subject, 'from' => 'dashboard']))
            ->assertOk()
            ->assertSee('Back to Class Record')
            ->assertSee(route('teacher.classes.record', [$this->term->section, $this->subject, 'from' => 'dashboard']), false);
    }

    public function test_advisory_section_page_leads_back_to_my_advisory(): void
    {
        $this->actingAs($this->teacher)->get(route('teacher.classes.show', [$this->term->section, 'from' => 'advisory']))
            ->assertOk()->assertSee('Back to My Advisory');

        $this->actingAs($this->teacher)->get(route('teacher.teaching'))
            ->assertOk()->assertSee('from=teaching', false);
    }
}
