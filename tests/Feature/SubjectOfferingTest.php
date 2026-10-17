<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use App\Services\SubjectOffering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectOfferingTest extends TestCase
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
        $this->term->update(['semester' => '1st Semester']);
        $this->teacher = User::where('email', 'teacher@classrecord.test')->firstOrFail();
    }

    private function subject(string $code, ?string $semester, ?int $programId = null, string $year = '3rd Year'): Subject
    {
        return Subject::create([
            'program_id' => $programId ?? $this->term->section->program_id,
            'code'       => $code,
            'name'       => "Subject {$code}",
            'units'      => 3,
            'year_level' => $semester === null ? null : $year,
            'semester'   => $semester,
            'status'     => 'approved',
        ]);
    }

    public function test_only_this_semester_and_unset_subjects_of_the_program_are_offered(): void
    {
        $first   = $this->subject('SEM1', '1st Semester');
        $second  = $this->subject('SEM2', '2nd Semester');
        $unset   = $this->subject('UNSET', null);
        $foreign = $this->subject('BA1', '1st Semester', Program::where('code', 'BSBA')->firstOrFail()->id);

        $codes = SubjectOffering::forTerm($this->term)->pluck('code')->all();

        $this->assertSame(['SEM1', 'UNSET'], $codes);
        $this->assertNull(SubjectOffering::refusal($this->term, $first));
        $this->assertNull(SubjectOffering::refusal($this->term, $unset));
        $this->assertStringContainsString('2nd Semester subject', SubjectOffering::refusal($this->term, $second));
        $this->assertStringContainsString('does not belong', SubjectOffering::refusal($this->term, $foreign));
    }

    public function test_summer_term_offers_any_semester(): void
    {
        $this->subject('SEM1', '1st Semester');
        $this->subject('SEM2', '2nd Semester');
        $this->subject('SUM1', 'Summer');
        $this->term->update(['semester' => 'Summer']);

        $this->assertSame(['SUM1', 'SEM1', 'SEM2'], SubjectOffering::forTerm($this->term->fresh())->pluck('code')->all());
    }

    public function test_program_head_cannot_assign_a_subject_of_another_semester(): void
    {
        $programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
        $second      = $this->subject('SEM2', '2nd Semester');
        $first       = $this->subject('SEM1', '1st Semester');

        $this->actingAs($programHead)->post(route('program-head.sections.attach-subject', $this->term->section), ['subject_id' => $second->id, 'teacher_id' => $this->teacher->id])
            ->assertSessionHasErrors('subject_id');
        $this->actingAs($programHead)->post(route('program-head.sections.attach-subject', $this->term->section), ['subject_id' => $first->id, 'teacher_id' => $this->teacher->id])
            ->assertSessionHasNoErrors();

        $this->assertFalse($this->term->subjects()->whereKey($second->id)->exists());
        $this->assertTrue($this->term->subjects()->whereKey($first->id)->exists());
    }

    public function test_dean_section_page_enforces_the_rule_and_saves_the_schedule(): void
    {
        $dean    = User::where('email', 'dean@classrecord.test')->firstOrFail();
        $foreign = $this->subject('BA1', '1st Semester', Program::where('code', 'BSBA')->firstOrFail()->id);
        $first   = $this->subject('SEM1', '1st Semester');

        $this->actingAs($dean)->post(route('dean.sections.attach-subject', $this->term->section), ['subject_id' => $foreign->id, 'teacher_id' => $this->teacher->id])
            ->assertSessionHasErrors('subject_id');

        $this->actingAs($dean)->post(route('dean.sections.attach-subject', $this->term->section), [
            'subject_id'     => $first->id,
            'teacher_id'     => $this->teacher->id,
            'schedule_days'  => ['M', 'W'],
            'schedule_start' => '08:00',
            'schedule_end'   => '09:30',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('section_subject_teachers', ['section_term_id' => $this->term->id, 'subject_id' => $first->id, 'schedule_days' => 'M,W']);
        $this->assertDatabaseMissing('section_subject_teachers', ['subject_id' => $foreign->id]);
    }

    public function test_dean_assignments_page_enforces_the_rule(): void
    {
        $dean   = User::where('email', 'dean@classrecord.test')->firstOrFail();
        $second = $this->subject('SEM2', '2nd Semester');

        $this->actingAs($dean)->post(route('dean.assignments.store'), ['section_id' => $this->term->section_id, 'subject_id' => $second->id, 'teacher_id' => $this->teacher->id])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('section_subject_teachers', ['subject_id' => $second->id]);
    }
}
