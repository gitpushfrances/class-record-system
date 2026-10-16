<?php

namespace Tests\Unit;

use App\Models\FinalGrade;
use App\Services\GradeCalculator;
use PHPUnit\Framework\TestCase;

class GradeConversionTest extends TestCase
{
    public static function standardProvider(): array
    {
        return [
            [100, 1.0], [98, 1.1], [96, 1.2], [95.99, 1.3], [94, 1.5], [93.99, 1.5],
            [91, 1.7], [90.99, 1.8], [89, 2.0], [88.99, 2.0], [86, 2.2], [85.99, 2.3],
            [83, 2.5], [82.99, 2.5], [80, 2.7], [79.99, 2.8], [77, 3.0], [76.99, 3.0],
            [75, 3.0], [74.99, 4.0], [70, 4.0], [69.99, 5.0], [0, 5.0],
        ];
    }

    public static function formulaProvider(): array
    {
        return [
            [100, 1.0, 1.0], [98, 1.1, 1.1], [88, 1.5, 1.4], [76, 2.0, 1.7],
            [74, 2.0, 1.8], [50, 3.0, 2.5], [33.3333, 3.7, 3.0], [0, 5.0, 4.0],
            [120, 1.0, 1.0], [-5, 5.0, 4.0],
        ];
    }

    public static function averageProvider(): array
    {
        return [
            [1.7, 1.4, 1.6, 1.6], [1.0, 1.0, 1.0, 1.0], [2.0, 3.0, 2.5, 2.5],
            [1.3, 1.4, 1.4, 1.4], [1.1, 1.2, 1.2, 1.2],
        ];
    }

    public static function formatProvider(): array
    {
        return [[1.25, 'standard', '1.3'], [1.25, 'formula_a', '1.3'], [3, 'standard', '3.0'], [1.04, 'formula_a', '1.0']];
    }

    /** @dataProvider standardProvider */
    public function test_standard_table($percentage, $expected): void
    {
        $this->assertEqualsWithDelta($expected, FinalGrade::convertToNumericalGrade($percentage, 'standard'), 0.0001);
    }

    /** @dataProvider formulaProvider */
    public function test_formulas($percentage, $major, $minor): void
    {
        $this->assertEqualsWithDelta($major, FinalGrade::convertToNumericalGrade($percentage, 'formula_a'), 0.0001);
        $this->assertEqualsWithDelta($minor, FinalGrade::convertToNumericalGrade($percentage, 'formula_b'), 0.0001);
    }

    /** @dataProvider averageProvider */
    public function test_average($mid, $fin, $standard, $formula): void
    {
        $this->assertEqualsWithDelta($standard, FinalGrade::averageGrade($mid, $fin, 'standard'), 0.0001);
        $this->assertEqualsWithDelta($formula, FinalGrade::averageGrade($mid, $fin, 'formula_a'), 0.0001);
    }

    /** @dataProvider formatProvider */
    public function test_format($grade, $method, $expected): void
    {
        $this->assertSame($expected, GradeCalculator::formatGrade($grade, $method));
    }

    public function test_passing_grade_and_attendance_keys(): void
    {
        $this->assertSame(3.0, GradeCalculator::PASSING_GRADE);
        $this->assertTrue(GradeCalculator::isAttendance('attendance'));
        $this->assertTrue(GradeCalculator::isAttendance('attendance_f'));
        $this->assertFalse(GradeCalculator::isAttendance('quiz'));
    }
}
