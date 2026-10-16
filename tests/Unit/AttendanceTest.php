<?php

namespace Tests\Unit;

use App\Models\AttendanceRecord;
use App\Services\GradeCalculator;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    private function rec(string $date, string $status, string $session = 'lecture')
    {
        return (object) ['date' => Carbon::parse($date), 'status' => $status, 'session_type' => $session];
    }

    private function enrollment(array $records)
    {
        return (object) ['attendanceRecords' => collect($records)];
    }

    public function test_streak_counts_trailing_absences_in_session_order(): void
    {
        $records = collect([
            $this->rec('2026-10-14', 'absent', 'laboratory'),
            $this->rec('2026-10-12', 'absent'),
            $this->rec('2026-10-14', 'absent'),
            $this->rec('2026-10-10', 'present'),
        ]);

        $this->assertSame(3, AttendanceRecord::consecutiveAbsences($records));
    }

    public function test_streak_resets_on_present(): void
    {
        $records = collect([
            $this->rec('2026-10-10', 'absent'),
            $this->rec('2026-10-12', 'present'),
            $this->rec('2026-10-14', 'absent'),
        ]);

        $this->assertSame(1, AttendanceRecord::consecutiveAbsences($records));
    }

    public function test_streak_is_zero_when_latest_is_present_or_empty(): void
    {
        $records = collect([$this->rec('2026-10-10', 'absent'), $this->rec('2026-10-12', 'present')]);

        $this->assertSame(0, AttendanceRecord::consecutiveAbsences($records));
        $this->assertSame(0, AttendanceRecord::consecutiveAbsences(collect()));
    }

    public function test_lecture_sorts_before_laboratory_on_the_same_date(): void
    {
        $records = collect([
            $this->rec('2026-10-14', 'absent', 'laboratory'),
            $this->rec('2026-10-14', 'present', 'lecture'),
        ]);

        $this->assertSame(1, AttendanceRecord::consecutiveAbsences($records));
    }

    public function test_each_session_counts_in_the_rate(): void
    {
        $enrollment = $this->enrollment([
            $this->rec('2026-10-14', 'present', 'lecture'),
            $this->rec('2026-10-14', 'absent', 'laboratory'),
            $this->rec('2026-10-16', 'present'),
        ]);
        $cutoff = Carbon::parse('2026-10-15');

        $this->assertSame(50.0, GradeCalculator::attendanceRate($enrollment, 'midterm', $cutoff));
        $this->assertSame(100.0, GradeCalculator::attendanceRate($enrollment, 'final', $cutoff));
    }

    public function test_rate_is_null_without_cutoff_or_records(): void
    {
        $enrollment = $this->enrollment([$this->rec('2026-10-14', 'present')]);

        $this->assertNull(GradeCalculator::attendanceRate($enrollment, 'midterm', null));
        $this->assertNull(GradeCalculator::attendanceRate($enrollment, 'final', Carbon::parse('2026-10-15')));
    }
}
