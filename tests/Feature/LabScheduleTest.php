<?php

namespace Tests\Feature;

use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use App\Services\ClassSchedule;
use App\Services\ReportOfGrades;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabScheduleTest extends TestCase
{
    use RefreshDatabase;

    private SectionTerm $term;
    private User $programHead;
    private User $teacher;
    private Subject $lab;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->term        = SectionTerm::where('status', 'active')
            ->whereHas('section.program', fn ($q) => $q->where('code', 'BSCS'))
            ->firstOrFail();
        $this->programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
        $this->teacher     = User::where('email', 'teacher@classrecord.test')->firstOrFail();
        $this->lab         = $this->subject('LAB301', true);
    }

    private function subject(string $code, bool $hasLab): Subject
    {
        return Subject::create([
            'code' => $code, 'name' => "Subject {$code}", 'units' => 3, 'status' => 'approved',
            'program_id' => $this->term->section->program_id, 'has_laboratory' => $hasLab,
            'year_level' => $this->term->section->year_level, 'semester' => $this->term->semester,
        ]);
    }

    private function assign(Subject $subject, array $schedule)
    {
        return $this->actingAs($this->programHead)->post(
            route('program-head.sections.attach-subject', $this->term->section),
            ['subject_id' => $subject->id, 'teacher_id' => $this->teacher->id] + $schedule
        );
    }

    public function test_lecture_and_laboratory_on_the_same_day_at_different_times_are_one_assignment(): void
    {
        $this->assign($this->lab, [
            'schedule_days' => ['T', 'Th'], 'schedule_start' => '08:00', 'schedule_end' => '09:30',
            'lab_days' => ['T'], 'lab_start' => '13:00', 'lab_end' => '16:00',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('section_subject_teachers', [
            'section_term_id' => $this->term->id, 'subject_id' => $this->lab->id, 'teacher_id' => $this->teacher->id,
            'schedule_days' => 'T,Th', 'lab_days' => 'T',
        ]);

        $pivot = $this->term->subjects()->whereKey($this->lab->id)->firstOrFail()->pivot;
        $this->assertSame('Lec 8:00-9:30AM (TTh); Lab 1:00-4:00PM (T)', ClassSchedule::summary($pivot, true));
    }

    public function test_overlapping_lecture_and_laboratory_are_refused(): void
    {
        $this->assign($this->lab, [
            'schedule_days' => ['T'], 'schedule_start' => '08:00', 'schedule_end' => '10:00',
            'lab_days' => ['T'], 'lab_start' => '09:00', 'lab_end' => '11:00',
        ])->assertSessionHasErrors('schedule_days');

        $this->assertFalse($this->term->subjects()->whereKey($this->lab->id)->exists());
    }

    public function test_another_subject_cannot_take_the_laboratory_time(): void
    {
        $this->assign($this->lab, ['lab_days' => ['F'], 'lab_start' => '13:00', 'lab_end' => '16:00'])->assertSessionHasNoErrors();

        $this->assign($this->subject('LEC302', false), ['schedule_days' => ['F'], 'schedule_start' => '15:00', 'schedule_end' => '16:30'])
            ->assertSessionHasErrors('schedule_days');

        $this->assertStringContainsString('LAB301', session('errors')->first('schedule_days'));
        $this->assertStringContainsString('laboratory', session('errors')->first('schedule_days'));
    }

    public function test_laboratory_fields_are_ignored_for_a_lecture_only_subject(): void
    {
        $lecture = $this->subject('LEC303', false);

        $this->assign($lecture, ['lab_days' => ['S'], 'lab_start' => '08:00', 'lab_end' => '11:00'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('section_subject_teachers', ['subject_id' => $lecture->id, 'lab_days' => null]);
    }

    public function test_the_same_subject_twice_is_refused_with_an_edit_hint(): void
    {
        $this->assign($this->lab, [])->assertSessionHasNoErrors();
        $this->assign($this->lab, [])->assertSessionHasErrors('subject_id');

        $this->assertStringContainsString('Use Edit', session('errors')->first('subject_id'));
        $this->assertSame(1, $this->term->subjects()->whereKey($this->lab->id)->count());
    }

    public function test_an_inactive_teacher_is_refused(): void
    {
        $this->teacher->update(['status' => 'inactive']);

        $this->assign($this->lab, [])->assertSessionHasErrors('teacher_id');
    }

    public function test_dean_assignments_and_section_page_save_the_laboratory_schedule(): void
    {
        $dean = User::where('email', 'dean@classrecord.test')->firstOrFail();

        $this->actingAs($dean)->post(route('dean.assignments.store'), [
            'section_id' => $this->term->section_id, 'subject_id' => $this->lab->id, 'teacher_id' => $this->teacher->id,
            'schedule_days' => ['M'], 'schedule_start' => '08:00', 'schedule_end' => '09:00',
            'lab_days' => ['M'], 'lab_start' => '13:00', 'lab_end' => '15:00',
        ])->assertSessionHas('success');

        $this->actingAs($dean)->post(route('dean.sections.change-subject-teacher', $this->term->section), [
            'subject_id' => $this->lab->id, 'teacher_id' => $this->teacher->id,
            'schedule_days' => ['M'], 'schedule_start' => '08:00', 'schedule_end' => '09:00',
            'lab_days' => ['W'], 'lab_start' => '13:00', 'lab_end' => '15:00',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('section_subject_teachers', ['subject_id' => $this->lab->id, 'lab_days' => 'W']);

        $this->actingAs($dean)->get(route('dean.assignments.index'))->assertOk()->assertSee('Lab 1:00-3:00PM (W)');
        $this->actingAs($dean)->get(route('dean.sections.show', $this->term->section))->assertOk()->assertSee('Laboratory schedule');
    }

    public function test_report_of_grades_prints_both_schedules(): void
    {
        $this->assign($this->lab, [
            'schedule_days' => ['T', 'Th'], 'schedule_start' => '08:00', 'schedule_end' => '09:30',
            'lab_days' => ['F'], 'lab_start' => '13:00', 'lab_end' => '16:00',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Lec 8:00-9:30AM (TTh); Lab 1:00-4:00PM (F)', ReportOfGrades::build($this->term, $this->lab)['schedule']);
    }

    public function test_program_head_chooses_lecture_only_or_lecture_and_laboratory(): void
    {
        $this->actingAs($this->programHead)->get(route('program-head.subjects.create'))
            ->assertOk()->assertSee('Lecture only')->assertSee('Lecture and laboratory');
    }
}
