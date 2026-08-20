<?php

namespace App\Services;

use App\Models\Program;
use App\Models\StudentNumberCounter;
use Illuminate\Support\Facades\DB;

class StudentNumberGenerator
{
    public function generate(int $programId): string
    {
        $program = Program::with('department')->findOrFail($programId);
        $year = now()->year;

        return DB::transaction(function () use ($program, $year) {
            $counter = StudentNumberCounter::where('department_id', $program->department_id)
                ->where('program_id', $program->id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                $counter = StudentNumberCounter::create([
                    'department_id' => $program->department_id,
                    'program_id'    => $program->id,
                    'year'          => $year,
                    'last_number'   => 0,
                ]);
            }

            $counter->increment('last_number');

            return sprintf(
                '%s-%s-%d-%06d',
                $program->department->code,
                $program->code,
                $year,
                $counter->last_number
            );
        });
    }
}
