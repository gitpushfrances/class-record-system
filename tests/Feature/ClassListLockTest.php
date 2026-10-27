<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\GradeVerification;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassListLockTest extends TestCase
{
    use RefreshDatabase;

    private User $programHead;
    private User $dean;
    private SectionTerm $term;
    private Subject $subject;
    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
        $this->dean        = User::where('email', 'dean@classrecord.test')->firstOrFail();
        $this->term        = SectionTerm::where('status', 'active')
            ->whereHas('section.program', fn ($q) => $q->where('code', 'BSCS'))
            ->firstOrFail();
        $this->subject     = Subject::firstOrFail();
        $this->enrollment  = $this->term->enrollments()->firstOrFail();

        $teacher = User::where('email', 'teacher@classrecord.test')->firstOrFail();
        $this->term->subjects()->attach($this->subject->id, ['teacher_id' => $teacher->id]);
    }

    private function submit(string $status): GradeVerification
    {
        return GradeVerification::create(['section_term_id' => $this->term->id, 'subject_id' => $this->subject->id, 'status' => $status]);
    }

    public function test_program_head_cannot_drop_or_restore_while_a_subject_is_submitted(): void
    {
        $this->submit('pending');

        $this->actingAs($this->programHead)->delete(route('program-head.sections.drop', [$this->term->section, $this->enrollment]))
            ->assertRedirect()->assertSessionHas('error', fn ($m) => str_contains($m, $this->subject->code));
        $this->assertDatabaseHas('enrollments', ['id' => $this->enrollment->id, 'status' => 'enrolled']);

        $this->enrollment->update(['status' => Enrollment::STATUS_DROPPED]);
        $this->actingAs($this->programHead)->post(route('program-head.sections.enroll', $this->term->section), ['student_id' => $this->enrollment->student_id])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('enrollments', ['id' => $this->enrollment->id, 'status' => 'dropped']);
    }

    public function test_drop_is_allowed_again_after_the_submission_is_rejected(): void
    {
        $this->submit('pending');

        $this->actingAs($this->programHead)->post(route('program-head.reject', [$this->term, $this->subject]), ['reason' => 'Student dropped the course'])
            ->assertRedirect();

        $this->actingAs($this->programHead)->delete(route('program-head.sections.drop', [$this->term->section, $this->enrollment]))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('enrollments', ['id' => $this->enrollment->id, 'status' => 'dropped']);
    }
}
