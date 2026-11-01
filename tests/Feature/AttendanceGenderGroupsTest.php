<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The attendance summary lists boys and girls apart, like the class record. */
class AttendanceGenderGroupsTest extends TestCase
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

        AcademicPeriod::query()->update(['is_active' => false]);
        AcademicPeriod::create(['school_year' => $this->term->academic_year, 'semester' => $this->term->semester, 'is_active' => true]);
    }

    private function summary()
    {
        return $this->actingAs($this->teacher)->get(route('teacher.attendance.summary', [$this->term->section, $this->subject]));
    }

    private function studentsOfGender(string $gender): int
    {
        return $this->term->rosterFor($this->subject->id)->with('student')->get()->filter(fn ($e) => $e->student?->gender === $gender)->count();
    }

    public function test_boys_and_girls_are_listed_in_separate_groups_with_their_counts(): void
    {
        $this->summary()->assertOk()->assertSeeInOrder([
            'Male (' . $this->studentsOfGender('male') . ')',
            'Female (' . $this->studentsOfGender('female') . ')',
        ]);
    }

    public function test_numbering_starts_again_in_each_group(): void
    {
        $content = $this->summary()->assertOk()->getContent();

        $this->assertSame(2, substr_count($content, '>1. '));
    }

    public function test_a_student_without_a_gender_is_not_dropped(): void
    {
        $enrollment = $this->term->rosterFor($this->subject->id)->with('student')->get()->firstWhere('student.gender', 'male');
        $enrollment->student->update(['gender' => null]);

        $this->summary()->assertOk()->assertSee('No gender set (1)');
    }

    public function test_grouping_changes_no_total(): void
    {
        $enrollment = $this->term->rosterFor($this->subject->id)->with('student')->get()->firstWhere('student.gender', 'female');
        foreach (['2024-10-01', '2024-10-02', '2024-10-03'] as $date) {
            AttendanceRecord::create([
                'enrollment_id' => $enrollment->id, 'subject_id' => $this->subject->id, 'date' => $date,
                'session_type' => 'lecture', 'status' => $date === '2024-10-02' ? 'absent' : 'present', 'recorded_by' => $this->teacher->id,
            ]);
        }

        $this->summary()->assertOk()->assertSee('66.67%')->assertSee('title="Absent"', false);
    }
}
