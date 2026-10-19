<?php

namespace Tests\Feature;

use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeConfigOrderTest extends TestCase
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
    }

    private function save(array $midterm): void
    {
        $components = array_merge($midterm, [['key' => 'exam_final', 'label' => 'Final Exam', 'weight' => 100, 'period' => 'final']]);

        $this->actingAs($this->teacher)->post(route('teacher.grades.config.store', [$this->term->section, $this->subject]), [
            'components' => $components, 'computation_method' => 'standard',
        ])->assertSessionHasNoErrors();
    }

    public function test_the_saved_order_is_the_class_record_column_order(): void
    {
        $attendance = ['key' => 'attendance', 'label' => 'Attendance', 'weight' => 10, 'period' => 'midterm'];
        $exam       = ['key' => 'exam', 'label' => 'Exam', 'weight' => 40, 'period' => 'midterm'];
        $quiz       = ['key' => 'quiz', 'label' => 'Quiz', 'weight' => 50, 'period' => 'midterm'];
        $record     = route('teacher.classes.record', [$this->term->section, $this->subject]);

        $this->save([$attendance, $exam, $quiz]);
        $this->assertSame(['attendance', 'exam', 'quiz', 'exam_final'], array_column($this->term->gradeConfigurationFor($this->subject->id)->getComponents(), 'key'));
        $this->actingAs($this->teacher)->get($record)->assertOk()->assertSeeInOrder(['Attendance (10%)', 'Exam (40%)', 'Quiz (50%)', 'Final Exam (100%)']);

        // Moved: Quiz first, Attendance last.
        $this->save([$quiz, $exam, $attendance]);
        $this->actingAs($this->teacher)->get($record)->assertOk()->assertSeeInOrder(['Quiz (50%)', 'Exam (40%)', 'Attendance (10%)', 'Final Exam (100%)']);
    }

    public function test_grade_config_has_move_up_and_down(): void
    {
        $this->actingAs($this->teacher)->get(route('teacher.grades.config', [$this->term->section, $this->subject]))
            ->assertOk()
            ->assertSee('function moveRow(idx, dir)', false)
            ->assertSee("'Move up'", false);
    }
}
