<?php

namespace Tests\Unit;

use App\Models\GradeItem;
use Tests\TestCase;

class GradeItemAutoNameTest extends TestCase
{
    private function items(string ...$names)
    {
        return collect($names)->map(fn ($n) => (object) ['name' => $n]);
    }

    public function test_first_item_is_number_one(): void
    {
        $this->assertSame('Quiz 1', GradeItem::autoName(collect(), 'quiz', 'Quiz', 'midterm'));
    }

    public function test_next_number_follows_the_highest(): void
    {
        $this->assertSame('Quiz 4', GradeItem::autoName($this->items('Quiz 1', 'Quiz 3'), 'quiz', 'Quiz', 'midterm'));
    }

    public function test_custom_names_do_not_count(): void
    {
        $this->assertSame('Quiz 2', GradeItem::autoName($this->items('Quiz 1', 'Pop quiz'), 'quiz', 'Quiz', 'midterm'));
    }

    public function test_exam_is_named_by_period_and_allowed_once(): void
    {
        $this->assertSame('Midterm Examination', GradeItem::autoName(collect(), 'exam', 'Exam', 'midterm'));
        $this->assertSame('Final Examination', GradeItem::autoName(collect(), 'exam_final', 'Final Exam', 'final'));
        $this->assertNull(GradeItem::autoName($this->items('Midterm Examination'), 'exam', 'Exam', 'midterm'));
    }

    public function test_blank_label_falls_back_to_item(): void
    {
        $this->assertSame('Item 1', GradeItem::autoName(collect(), 'others_3', '  ', 'final'));
    }
}
