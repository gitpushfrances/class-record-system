<?php

namespace Tests\Feature;

use App\Models\GradeVerification;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveSearchAndLockNoticeTest extends TestCase
{
    use RefreshDatabase;

    private SectionTerm $term;
    private User $programHead;
    private User $dean;
    private User $teacher;

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
        $this->dean        = User::where('email', 'dean@classrecord.test')->firstOrFail();
        $this->teacher     = User::where('email', 'teacher@classrecord.test')->firstOrFail();
    }

    public function test_every_search_box_searches_while_typing(): void
    {
        $subject = Subject::firstOrFail();
        $this->term->subjects()->attach($subject->id, ['teacher_id' => $this->teacher->id]);

        $pages = [
            [$this->dean, route('dean.students.index')],
            [$this->dean, route('dean.faculty.index')],
            [$this->programHead, route('program-head.students.index')],
            [$this->teacher, route('teacher.classes.students', [$this->term->section, $subject])],
        ];

        foreach ($pages as [$user, $url]) {
            $this->actingAs($user)->get($url)->assertOk()
                ->assertSee('data-live-search', false)
                ->assertSee('data-live-input', false)
                ->assertSee('data-live-button', false);
        }
    }

    public function test_dialog_buttons_use_the_essu_green(): void
    {
        $this->actingAs($this->dean)->get(route('dean.students.index'))
            ->assertSee("window.Swal.mixin({ confirmButtonColor: '#0a5b05'", false);
    }

    public function test_locked_class_list_is_shown_before_anyone_clicks(): void
    {
        $subject = Subject::firstOrFail();
        GradeVerification::create(['section_term_id' => $this->term->id, 'subject_id' => $subject->id, 'status' => 'pending']);

        $this->actingAs($this->programHead)->get(route('program-head.sections.show', $this->term->section))
            ->assertOk()
            ->assertSee('Class list locked')
            ->assertSee($subject->code)
            ->assertDontSee('data-picker-input', false)
            ->assertDontSee('>Drop<', false);

        $this->actingAs($this->dean)->get(route('dean.enrollments.show', $this->term))
            ->assertOk()
            ->assertSee('Class list locked')
            ->assertDontSee('data-picker-input', false)
            ->assertDontSee('>Drop<', false);
    }

    public function test_unlocked_class_list_keeps_its_controls(): void
    {
        $this->actingAs($this->programHead)->get(route('program-head.sections.show', $this->term->section))
            ->assertOk()
            ->assertDontSee('Class list locked')
            ->assertSee('>Drop<', false);
    }
}
