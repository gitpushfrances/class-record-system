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

        $credit = $records->sum(fn($r) => match ($r->status) {
            'present', 'excused' => 1.0,
            'late'               => 0.5,
            default              => 0.0,
        });

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

    /** Formulas show 2 decimals; the standard table shows 1. */
    public static function formatGrade($grade, ?string $method = 'standard'): string
    {
        $decimals = in_array($method, ['formula_a', 'formula_b'], true) ? 2 : 1;
        return number_format((float) $grade, $decimals);
    }
}
