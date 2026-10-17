<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use App\Models\Section;
use App\Models\SectionTerm;
use Illuminate\Support\Facades\DB;

/**
 * Sets a section's term for the active academic period ("Set Term").
 * Same period: only the adviser changes. New period: the previous term is
 * closed (status completed; its students, grades and ROG stay attached to it)
 * and a new term is started, so last semester's records are never relabeled.
 */
class TermRollover
{
    /** @return array{0: SectionTerm, 1: ?SectionTerm} the active term, and the term that was closed (if any) */
    public static function setTerm(Section $section, int $adviserId, AcademicPeriod $period): array
    {
        return DB::transaction(function () use ($section, $adviserId, $period) {
            $current = $section->terms()->where('status', 'active')->lockForUpdate()->first();

            if ($current && $current->academic_year === $period->school_year && $current->semester === $period->semester) {
                $current->update(['adviser_id' => $adviserId]);

                return [$current, null];
            }

            $current?->update(['status' => 'completed']);

            $term = SectionTerm::updateOrCreate(
                ['section_id' => $section->id, 'academic_year' => $period->school_year, 'semester' => $period->semester],
                ['adviser_id' => $adviserId, 'status' => 'active']
            );

            return [$term, $current];
        });
    }

    public static function message(SectionTerm $term, ?SectionTerm $closed): string
    {
        if (!$closed) {
            return 'Term saved successfully.';
        }

        return "New term started: {$term->semester} {$term->academic_year}. The previous term ({$closed->semester} {$closed->academic_year}) is closed; its students and grades are kept. Enroll this term's students next.";
    }
}
