<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use App\Services\AttendanceSheet;
use App\Services\TermRollover;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSheetTest extends TestCase
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
        $this->subject->update(['has_laboratory' => true]);
        $this->term->subjects()->attach($this->subject->id, ['teacher_id' => $this->teacher->id]);

        AcademicPeriod::query()->update(['is_active' => false]);
        AcademicPeriod::create([
            'school_year' => $this->term->academic_year, 'semester' => $this->term->semester,
            'midterm_cutoff_date' => '2024-10-15', 'is_active' => true,
        ]);

        [$first, $second] = $this->term->enrollments()->orderBy('id')->take(2)->get()->all();
        $this->mark($first, '2024-11-05', 'lecture', 'absent');
        $this->mark($first, '2024-10-01', 'laboratory', 'absent');
        $this->mark($first, '2024-10-01', 'lecture', 'present');
        $this->mark($second, '2024-10-01', 'lecture', 'present');
    }

    private function mark($enrollment, string $date, string $session, string $status): void
    {
        AttendanceRecord::create([
            'enrollment_id' => $enrollment->id, 'subject_id' => $this->subject->id, 'date' => $date,
            'session_type' => $session, 'status' => $status, 'recorded_by' => $this->teacher->id,
        ]);
    }

    public function test_sessions_are_columns_in_date_order_split_by_the_midterm_cutoff(): void
    {
        $sheet = AttendanceSheet::build($this->term, $this->subject, '2024-10-15');

        $this->assertSame(
            ['2024-10-01|lecture', '2024-10-01|laboratory', '2024-11-05|lecture'],
            $sheet['sessions']->pluck('key')->all()
        );
        $this->assertSame(['midterm', 'midterm', 'final'], $sheet['sessions']->pluck('period')->all());

        $first = $this->term->enrollments()->orderBy('id')->firstOrFail();
        $row   = $sheet['rows']->first(fn ($r) => $r['student']->id === $first->student_id);
        $this->assertSame(['present', 'absent', 'absent'], [$row['marks']['2024-10-01|lecture'], $row['marks']['2024-10-01|laboratory'], $row['marks']['2024-11-05|lecture']]);
        $this->assertSame([3, 1, 2, 2], [$row['total'], $row['present'], $row['absent'], $row['streak']]);
    }

    public function test_without_a_cutoff_all_sessions_are_one_group(): void
    {
        $sheet = AttendanceSheet::build($this->term, $this->subject, null);

        $this->assertSame([null, null, null], $sheet['sessions']->pluck('period')->all());
    }

    public function test_summary_page_shows_each_session_with_present_and_absent_marks(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.attendance.summary', [$this->term->section, $this->subject]))
            ->assertOk()
            ->assertSeeInOrder(['Midterm', 'Finals', 'Oct 1', 'Lec', 'Oct 1', 'Lab', 'Nov 5'])
            ->assertSee('title="Present"', false)
            ->assertSee('title="Absent"', false)
            ->assertSee('title="No record"', false)
            ->assertSee('Back to Attendance');
    }

    public function test_a_closed_term_shows_its_summary_in_past_classes(): void
    {
        AcademicPeriod::query()->update(['is_active' => false]);
        $period = AcademicPeriod::create(['school_year' => '2099-2100', 'semester' => $this->term->semester, 'is_active' => true]);
        TermRollover::setTerm($this->term->section, $this->teacher->id, $period);

        $this->actingAs($this->teacher)
            ->get(route('teacher.past-classes.attendance', [$this->term, $this->subject]))
            ->assertOk()
            ->assertSeeInOrder(['Midterm', 'Finals', 'Oct 1', 'Nov 5'])
            ->assertSee('Back to Class Record');
    }
}
