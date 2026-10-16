<?php

namespace Tests\Unit;

use App\Models\GradeConfiguration;
use App\Services\GradeCalculator;
use Tests\TestCase;

class GradeIncTest extends TestCase
{
    private function config(array $extra = []): GradeConfiguration
    {
        $config = new GradeConfiguration();
        $config->config_json = array_merge([
            ['key' => 'quiz', 'label' => 'Quiz', 'weight' => 30, 'period' => 'midterm'],
            ['key' => 'exam', 'label' => 'Midterm Exam', 'weight' => 70, 'period' => 'midterm'],
            ['key' => 'project', 'label' => 'Project', 'weight' => 40, 'period' => 'final'],
            ['key' => 'exam_f', 'label' => 'Final Exam', 'weight' => 60, 'period' => 'final'],
        ], $extra);

        return $config;
    }

    private function grade(string $type, string $period, float $score)
    {
        return (object) [
            'score'     => $score,
            'gradeItem' => (object) ['component_type' => $type, 'period' => $period, 'max_score' => 50],
        ];
    }

    private function enrollment(array $grades)
    {
        return (object) ['studentGrades' => collect($grades), 'attendanceRecords' => collect()];
    }

    private function complete(): array
    {
        return [
            $this->grade('quiz', 'midterm', 20),
            $this->grade('exam', 'midterm', 40),
            $this->grade('project', 'final', 30),
            $this->grade('exam_f', 'final', 45),
        ];
    }

    public function test_complete_student_is_not_inc(): void
    {
        $this->assertSame([], GradeCalculator::incompletePeriods($this->enrollment($this->complete()), $this->config(), null));
    }

    public function test_blank_among_other_scores_is_not_inc(): void
    {
        $grades = array_merge($this->complete(), [$this->grade('quiz', 'midterm', 25)]);
        $this->assertSame([], GradeCalculator::incompletePeriods($this->enrollment($grades), $this->config(), null));
    }

    public function test_component_with_no_scores_is_inc(): void
    {
        $grades = array_values(array_filter($this->complete(), fn($g) => $g->gradeItem->component_type !== 'quiz'));
        $this->assertSame(['midterm' => ['Quiz']], GradeCalculator::incompletePeriods($this->enrollment($grades), $this->config(), null));
    }

    public function test_component_total_zero_is_inc(): void
    {
        $grades = array_values(array_filter($this->complete(), fn($g) => $g->gradeItem->component_type !== 'quiz'));
        $grades = array_merge($grades, [
            $this->grade('quiz', 'midterm', 0),
            $this->grade('quiz', 'midterm', 0),
            $this->grade('quiz', 'midterm', 0),
        ]);
        $this->assertSame(['midterm' => ['Quiz']], GradeCalculator::incompletePeriods($this->enrollment($grades), $this->config(), null));
    }

    public function test_missing_final_exam_marks_only_final(): void
    {
        $grades = array_values(array_filter($this->complete(), fn($g) => $g->gradeItem->component_type !== 'exam_f'));
        $this->assertSame(['final' => ['Final Exam']], GradeCalculator::incompletePeriods($this->enrollment($grades), $this->config(), null));
    }

    public function test_zero_weight_component_is_ignored(): void
    {
        $config = $this->config([['key' => 'other', 'label' => 'Other', 'weight' => 0, 'period' => 'midterm']]);
        $this->assertSame([], GradeCalculator::incompletePeriods($this->enrollment($this->complete()), $config, null));
    }

    public function test_attendance_without_cutoff_is_inc(): void
    {
        $config = $this->config([['key' => 'attendance', 'label' => 'Attendance', 'weight' => 10, 'period' => 'midterm']]);
        $this->assertSame(['midterm' => ['Attendance']], GradeCalculator::incompletePeriods($this->enrollment($this->complete()), $config, null));
    }

    public function test_apply_inc_to_final_only(): void
    {
        $result = [
            'midterm_percentage' => 85.0, 'midterm_numerical' => 1.4,
            'final_percentage' => 70.0, 'final_numerical' => 2.2,
            'average_numerical' => 1.8, 'final_grade' => 77.5, 'numerical_grade' => 1.8,
            'letter_grade' => '1.8', 'remarks' => 'passed',
        ];
        $out = GradeCalculator::applyInc($result, ['final' => ['Final Exam']]);

        $this->assertSame(85.0, $out['midterm_percentage']);
        $this->assertSame(1.4, $out['midterm_numerical']);
        $this->assertNull($out['final_percentage']);
        $this->assertNull($out['final_numerical']);
        $this->assertNull($out['average_numerical']);
        $this->assertNull($out['numerical_grade']);
        $this->assertNull($out['final_grade']);
        $this->assertSame('INC', $out['letter_grade']);
        $this->assertSame('incomplete', $out['remarks']);
    }

    public function test_apply_inc_without_missing_changes_nothing(): void
    {
        $result = ['midterm_numerical' => 1.4, 'final_numerical' => 2.2, 'remarks' => 'passed'];
        $this->assertSame($result, GradeCalculator::applyInc($result, []));
    }
}
