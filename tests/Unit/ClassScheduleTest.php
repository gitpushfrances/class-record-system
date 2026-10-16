<?php

namespace Tests\Unit;

use App\Services\ClassSchedule;
use Illuminate\Http\Request;
use Tests\TestCase;

class ClassScheduleTest extends TestCase
{
    public function test_label_matches_the_report_of_grades_format(): void
    {
        $this->assertSame('10:30-12:00PM (MW)', ClassSchedule::label('M,W', '10:30:00', '12:00:00'));
        $this->assertSame('1:00-2:30PM (TTh)', ClassSchedule::label('T,Th', '13:00', '14:30'));
        $this->assertSame('', ClassSchedule::label(null, null, null));
    }

    public function test_days_are_saved_in_week_order(): void
    {
        $request = Request::create('/', 'POST', ['schedule_days' => ['F', 'M', 'Th'], 'schedule_start' => '08:00', 'schedule_end' => '09:00']);

        $this->assertSame('M,Th,F', ClassSchedule::fromRequest($request)['schedule_days']);
    }

    public function test_weekend_days_are_supported(): void
    {
        $request = Request::create('/', 'POST', ['schedule_days' => ['Su', 'S', 'M'], 'schedule_start' => '08:00', 'schedule_end' => '11:00']);

        $this->assertSame('M,S,Su', ClassSchedule::fromRequest($request)['schedule_days']);
        $this->assertSame('8:00-11:00AM (SSu)', ClassSchedule::label('S,Su', '08:00', '11:00'));
    }

    public function test_no_schedule_saves_nulls(): void
    {
        $this->assertSame(
            ['schedule_days' => null, 'schedule_start' => null, 'schedule_end' => null],
            ClassSchedule::fromRequest(Request::create('/', 'POST', []))
        );
    }
}
