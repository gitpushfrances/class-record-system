<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\GradeConfiguration;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use App\Services\RecordingWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Attendance and grade items can only be dated inside the term's cutoffs, and recording is closed when the period is incomplete. */
class RecordingWindowTest extends TestCase
{
    use RefreshDatabase;

    private const COMPONENTS = [
        ['key' => 'quiz', 'label' => 'Quiz', 'weight' => 100, 'period' => 'midterm'],
        ['key' => 'exam_final', 'label' => 'Final Exam', 'weight' => 100, 'period' => 'final'],
    ];

    private const DATES = ['start_date' => '2026-08-03', 'midterm_cutoff_date' => '2026-10-02', 'finals_cutoff_date' => '2026-12-04'];

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
            'computation_method' => 'standard', 'status' => 'active', 'config_json' => self::COMPONENTS,
        ]);

        $this->period(self::DATES);
    }

    private function period(array $dates): AcademicPeriod
    {
        AcademicPeriod::query()->delete();

        return AcademicPeriod::create($dates + [
            'school_year' => $this->term->academic_year, 'semester' => $this->term->semester, 'is_active' => true,
        ]);
    }

    private function attend(string $date)
    {
        $enrollment = $this->term->rosterFor($this->subject->id)->firstOrFail();

        return $this->actingAs($this->teacher)->post(route('teacher.attendance.store', [$this->term->section, $this->subject]), [
            'date' => $date, 'session' => 'lecture',
            'attendance' => [['enrollment_id' => $enrollment->id, 'status' => 'present']],
        ]);
    }

    private function item(string $component, ?string $date)
    {
        return $this->actingAs($this->teacher)->post(route('teacher.grades.items.store', [$this->term->section, $this->subject]), [
            'component_type' => $component, 'max_score' => 20, 'date_given' => $date,
        ]);
    }

    private function items(): int
    {
        return $this->term->gradeItemsFor($this->subject->id)->count();
    }

    private function records(): int
    {
        return AttendanceRecord::where('subject_id', $this->subject->id)->count();
    }

    public function test_the_bounds_follow_the_period_of_the_term(): void
    {
        $bounds = RecordingWindow::bounds($this->term);

        $this->assertSame(['2026-08-03', '2026-12-04'], $bounds['semester']);
        $this->assertSame(['2026-08-03', '2026-10-02'], $bounds['midterm']);
        $this->assertSame(['2026-10-03', '2026-12-04'], $bounds['final']);
    }

    public function test_attendance_inside_the_semester_is_saved_including_the_first_and_last_day(): void
    {
        foreach (['2026-08-03', '2026-09-15', '2026-12-04'] as $date) {
            $this->attend($date)->assertSessionHas('success');
        }

        $this->assertSame(3, $this->records());
    }

    public function test_attendance_after_the_finals_cutoff_or_before_the_start_is_refused(): void
    {
        $this->attend('2026-12-05')->assertSessionHas('error');
        $this->attend('2026-08-02')->assertSessionHas('error');

        $this->assertSame(0, $this->records());
    }

    public function test_a_midterm_item_cannot_be_dated_after_the_midterm_cutoff(): void
    {
        $this->item('quiz', '2026-10-03')->assertSessionHasErrors('date_given');
        $this->item('quiz', '2026-08-02')->assertSessionHasErrors('date_given');
        $this->assertSame(0, $this->items());

        $this->item('quiz', '2026-10-02')->assertSessionHas('success');
        $this->item('quiz', '2026-09-01')->assertSessionHas('success');
        $this->assertSame(2, $this->items());
    }

    public function test_a_finals_item_cannot_be_dated_on_or_before_the_midterm_cutoff_or_after_the_finals_cutoff(): void
    {
        $this->item('exam_final', '2026-10-02')->assertSessionHasErrors('date_given');
        $this->item('exam_final', '2026-12-05')->assertSessionHasErrors('date_given');
        $this->assertSame(0, $this->items());

        $this->item('exam_final', '2026-10-03')->assertSessionHas('success');
        $this->assertSame(1, $this->items());
    }

    public function test_an_item_left_without_a_date_is_dated_today_in_manila(): void
    {
        // 04:00 UTC is noon in Manila, the same calendar day.
        $this->travelTo('2026-09-10 04:00:00');

        $this->item('quiz', null)->assertSessionHas('success');

        $this->assertSame('2026-09-10', $this->term->gradeItemsFor($this->subject->id)->firstOrFail()->date_given->toDateString());

        $this->travelBack();
    }

    public function test_an_item_left_without_a_date_is_refused_when_today_is_outside_its_period(): void
    {
        $this->travelTo('2027-03-01 04:00:00');

        $this->item('quiz', null)->assertSessionHasErrors('date_given');
        $this->item('exam_final', '')->assertSessionHasErrors('date_given');
        $this->assertSame(0, $this->items());

        $this->travelBack();
    }

    public function test_today_follows_the_manila_clock_not_utc(): void
    {
        // 17:00 UTC on Oct 2 is already 01:00 on Oct 3 in Manila, after the midterm cutoff.
        $this->travelTo('2026-10-02 17:00:00');

        $this->assertSame('2026-10-03', RecordingWindow::today());
        $this->item('quiz', null)->assertSessionHasErrors('date_given');

        $this->travelBack();
    }

    public function test_the_refusal_says_which_dates_are_allowed(): void
    {
        $message = RecordingWindow::refusal($this->term, '2026-12-05');

        $this->assertStringContainsString('Dec 5, 2026 is outside this semester', $message);
        $this->assertStringContainsString('from Aug 3, 2026 to Dec 4, 2026', $message);
    }

    public function test_recording_is_closed_when_the_term_has_no_period(): void
    {
        AcademicPeriod::query()->delete();

        $this->attend('2026-09-01')->assertSessionHas('error', RecordingWindow::NOT_CONFIGURED);
        $this->item('quiz', '2026-09-01')->assertSessionHasErrors(['date_given' => RecordingWindow::NOT_CONFIGURED]);
        $this->item('quiz', null)->assertSessionHasErrors(['date_given' => RecordingWindow::NOT_CONFIGURED]);

        $this->assertSame(0, $this->records());
        $this->assertSame(0, $this->items());
        $this->assertSame([null, null], RecordingWindow::bounds($this->term)['semester']);
        $this->assertSame(RecordingWindow::NOT_CONFIGURED, RecordingWindow::problem($this->term));
    }

    public function test_recording_is_closed_when_the_period_misses_a_date(): void
    {
        foreach (['start_date', 'midterm_cutoff_date', 'finals_cutoff_date'] as $missing) {
            $this->period(array_diff_key(self::DATES, [$missing => true]));

            $this->attend('2026-09-01')->assertSessionHas('error', RecordingWindow::NOT_CONFIGURED);
            $this->item('quiz', '2026-09-01')->assertSessionHasErrors(['date_given' => RecordingWindow::NOT_CONFIGURED]);
        }

        $this->assertSame(0, $this->records());
        $this->assertSame(0, $this->items());
    }

    public function test_the_attendance_page_limits_the_date_picker_and_starts_inside_the_semester(): void
    {
        $this->travelTo('2027-03-01 04:00:00');

        $this->actingAs($this->teacher)->get(route('teacher.attendance.index', [$this->term->section, $this->subject]))
            ->assertOk()
            ->assertSee('min="2026-08-03"', false)
            ->assertSee('max="2026-12-04"', false)
            ->assertSee('value="2026-12-04"', false)
            ->assertSee('Allowed dates: from Aug 3, 2026 to Dec 4, 2026.')
            ->assertSee('Saves attendance for Dec 4, 2026.');

        $this->travelBack();
    }

    public function test_the_attendance_page_says_so_when_recording_is_closed(): void
    {
        AcademicPeriod::query()->delete();

        $html = $this->actingAs($this->teacher)->get(route('teacher.attendance.index', [$this->term->section, $this->subject]))
            ->assertOk()
            ->assertSee(RecordingWindow::NOT_CONFIGURED)
            ->assertSee('Attendance cannot be saved for this date.')
            ->getContent();

        $this->assertMatchesRegularExpression('/<button type="submit"[^>]*\bdisabled\b[^>]*>\s*Save Attendance/', $html);
    }

    public function test_the_grade_items_page_shows_the_allowed_dates_and_closes_when_the_period_is_incomplete(): void
    {
        $url = route('teacher.grades.items', [$this->term->section, $this->subject]);

        $this->actingAs($this->teacher)->get($url)
            ->assertOk()
            ->assertSee('Allowed dates: from Aug 3, 2026 to Oct 2, 2026.')
            ->assertSee('Allowed dates: from Oct 3, 2026 to Dec 4, 2026.')
            ->assertDontSee('Grade items cannot be added until it is fixed.');

        $this->period(['midterm_cutoff_date' => '2026-10-02', 'finals_cutoff_date' => '2026-12-04']);

        $this->actingAs($this->teacher)->get($url)
            ->assertOk()
            ->assertSee(RecordingWindow::NOT_CONFIGURED)
            ->assertSee('Grade items cannot be added until it is fixed.');
    }
}
