<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use App\Models\StudentNumberCounter;
use Illuminate\Support\Facades\DB;

class StudentNumberGenerator
{
    public function generate(): string
    {
        $period = AcademicPeriod::getActive();

        if (!$period) {
            throw new \App\Exceptions\NoActiveAcademicPeriodException();
        }

        $startYear = (int) explode('-', $period->school_year)[0];
        $yy = substr((string) $startYear, -2);

        return DB::transaction(function () use ($startYear, $yy) {
            $counter = StudentNumberCounter::where('year', $startYear)
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                $counter = StudentNumberCounter::create([
                    'year'        => $startYear,
                    'last_number' => 0,
                ]);
            }

            $counter->increment('last_number');

            return sprintf('%s-%04d', $yy, $counter->last_number);
        });
    }
}
