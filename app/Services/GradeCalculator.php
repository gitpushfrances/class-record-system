<?php

namespace App\Services;

use App\Models\FinalGrade;
use App\Models\GradeConfiguration;

class GradeCalculator
{
    public const PASSING_GRADE = 3.00;

    public static function isAttendance(string $key): bool
    {
        return in_array($key, ['attendance', 'attendance_f'], true);
    }

    public static function attendanceRate($enrollment, string $period, $cutoffDate): ?float
    {
        if (!$cutoffDate) {
            return null;
        }

        $records = $enrollment->attendanceRecords->filter(
            fn($r) => $period === 'midterm' ? $r->date->lte($cutoffDate) : $r->date->gt($cutoffDate)
        );

        if ($records->isEmpty()) {
            return null;
        }

        $credit = $records->where('status', 'present')->count();

        return round(($credit / $records->count()) * 100, 2);
    }

    /**
     * Weighted points per component for one period, plus the period percentage.
     * Percentage is null when the period has no recorded data yet.
     */
    public static function period($enrollment, GradeConfiguration $config, string $period, $cutoffDate): array
    {
        $scores       = [];
        $activeWeight = 0.0;

        foreach ($config->getComponentsByPeriod($period) as $comp) {
            $key    = $comp['key'];
            $weight = (float) $comp['weight'];
            $scores[$key] = 0.0;
            if ($weight === 0.0) continue;

            if (self::isAttendance($key)) {
                $rate = self::attendanceRate($enrollment, $period, $cutoffDate);
                if ($rate !== null) {
                    $scores[$key]  = round(($rate / 100) * $weight, 2);
                    $activeWeight += $weight;
                }
                continue;
            }

            $items = $enrollment->studentGrades->filter(
                fn($g) => $g->gradeItem !== null
                    && $g->gradeItem->component_type === $key
                    && $g->gradeItem->period === $period
            );

            if ($items->isNotEmpty()) {
                $earned   = $items->sum(fn($g) => (float) $g->score);
                $possible = $items->sum(fn($g) => (float) $g->gradeItem->max_score);
                $scores[$key]  = $possible > 0 ? round(($earned / $possible) * $weight, 2) : 0.0;
                $activeWeight += $weight;
            }
        }

        if ($activeWeight <= 0) {
            return ['scores' => $scores, 'percentage' => null];
        }

        if ($activeWeight < 100) {
            $factor = 100 / $activeWeight;
            foreach ($scores as $k => $v) {
                $scores[$k] = round($v * $factor, 2);
            }
        }

        return ['scores' => $scores, 'percentage' => round(array_sum($scores), 2)];
    }

    public static function incompletePeriods($enrollment, GradeConfiguration $config, $cutoffDate): array
    {
        $missing = [];

        foreach (['midterm', 'final'] as $period) {
            foreach ($config->getComponentsByPeriod($period) as $comp) {
                $key = $comp['key'];
                if ((float) $comp['weight'] === 0.0) continue;

                if (self::isAttendance($key)) {
                    $earned = self::attendanceRate($enrollment, $period, $cutoffDate) ?? 0.0;
                } else {
                    $earned = $enrollment->studentGrades
                        ->filter(fn($g) => $g->gradeItem !== null
                            && $g->gradeItem->component_type === $key
                            && $g->gradeItem->period === $period)
                        ->sum(fn($g) => (float) $g->score);
                }

                if ($earned <= 0) {
                    $missing[$period][] = $comp['label'] ?? $key;
                }
            }
        }

        return $missing;
    }

    /** The student's result under the INC rule: what the teacher sees, what is submitted and what the ROG prints. */
    public static function result($enrollment, GradeConfiguration $config, $cutoffDate): array
    {
        return self::applyInc(
            self::summary($enrollment, $config, $cutoffDate),
            self::incompletePeriods($enrollment, $config, $cutoffDate)
        );
    }

    public static function applyInc(array $result, array $missing): array
    {
        if (isset($missing['midterm'])) {
            $result['midterm_percentage'] = null;
            $result['midterm_numerical']  = null;
        }

        if (isset($missing['final'])) {
            $result['final_percentage'] = null;
            $result['final_numerical']  = null;
        }

        if ($missing) {
            $result['final_grade']       = null;
            $result['average_numerical'] = null;
            $result['numerical_grade']   = null;
            $result['letter_grade']      = 'INC';
            $result['remarks']           = 'incomplete';
        }

        return $result;
    }

    /**
     * One student's complete result. A period with no data is null and is left
     * out of the average instead of counting as 0.
     */
    public static function summary($enrollment, GradeConfiguration $config, $cutoffDate): array
    {
        $method = $config->computation_method ?? 'standard';

        $mid = self::period($enrollment, $config, 'midterm', $cutoffDate);
        $fin = self::period($enrollment, $config, 'final', $cutoffDate);

        $midNum = $mid['percentage'] !== null ? FinalGrade::convertToNumericalGrade($mid['percentage'], $method) : null;
        $finNum = $fin['percentage'] !== null ? FinalGrade::convertToNumericalGrade($fin['percentage'], $method) : null;

        $percentages = array_values(array_filter([$mid['percentage'], $fin['percentage']], fn($v) => $v !== null));
        $grades      = array_values(array_filter([$midNum, $finNum], fn($v) => $v !== null));

        $avgPct = $percentages ? round(array_sum($percentages) / count($percentages), 2) : null;
        $avgNum = $grades ? FinalGrade::averageGrade($grades[0], $grades[1] ?? $grades[0], $method) : null;

        return [
            'scores'             => $mid['scores'] + $fin['scores'],
            'midterm_percentage' => $mid['percentage'],
            'midterm_numerical'  => $midNum,
            'final_percentage'   => $fin['percentage'],
            'final_numerical'    => $finNum,
            'average_numerical'  => $avgNum,
            'final_grade'        => $avgPct,
            'numerical_grade'    => $avgNum,
            'letter_grade'       => $avgNum !== null ? self::formatGrade($avgNum, $method) : '—',
            'remarks'            => $avgNum === null ? null : ($avgNum <= self::PASSING_GRADE ? 'passed' : 'failed'),
        ];
    }

    /** Grades display to one decimal. */
    public static function formatGrade($grade, ?string $method = 'standard'): string
    {
        return number_format((float) $grade, 1);
    }
}
