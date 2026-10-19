<?php

namespace App\Console\Commands;

use App\Models\AcademicPeriod;
use App\Models\FinalGrade;
use App\Models\GradeVerification;
use App\Services\GradeCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecomputeFinalGrades extends Command
{
    protected $signature = 'grades:recompute
                            {--apply : Write the recomputed values (default is a dry run)}
                            {--include-verified : Also change rows the Program Head already verified}';

    protected $description = 'Recompute stored final grades under the current grading rules (dry run unless --apply)';

    public function handle(): int
    {
        $apply           = (bool) $this->option('apply');
        $includeVerified = (bool) $this->option('include-verified');

        $verifications = GradeVerification::all()
            ->keyBy(fn ($v) => $v->section_term_id . '-' . $v->subject_id);

        $periods = AcademicPeriod::all()
            ->keyBy(fn ($p) => $p->school_year . '|' . $p->semester);

        $report       = [];
        $updates      = [];
        $skipped      = [];
        $heldVerified = 0;
        $checked      = 0;

        FinalGrade::with(['enrollment.sectionTerm.section', 'enrollment.student', 'subject'])
            ->whereNotNull('subject_id')
            ->chunkById(200, function ($finals) use (&$report, &$updates, &$skipped, &$heldVerified, &$checked, $verifications, $periods, $includeVerified) {
                foreach ($finals as $fg) {
                    $checked++;

                    $enrollment = $fg->enrollment;
                    $term       = $enrollment?->sectionTerm;
                    $section    = $term?->section;
                    $label      = ($enrollment?->student?->full_name ?? "Enrollment {$fg->enrollment_id}")
                                . ' / ' . ($fg->subject?->code ?? "Subject {$fg->subject_id}");

                    if (!$section) {
                        $skipped['no section or term found'][] = $label;
                        continue;
                    }

                    $config = $term->gradeConfigurationFor($fg->subject_id);
                    if (!$config) {
                        $skipped['no grade configuration'][] = $label;
                        continue;
                    }

                    // The cutoff date belongs to the row's own academic period, not the active one.
                    $cutoff = $periods->get($term->academic_year . '|' . $term->semester)?->midterm_cutoff_date;

                    $usesAttendance = collect(['midterm', 'final'])
                        ->flatMap(fn ($p) => $config->getComponentsByPeriod($p))
                        ->contains(fn ($c) => GradeCalculator::isAttendance($c['key']) && (float) $c['weight'] > 0);

                    if ($usesAttendance && !$cutoff) {
                        $skipped['attendance is graded but that academic period has no midterm cutoff date'][] = $label;
                        continue;
                    }

                    $enrollment->load([
                        'studentGrades' => fn ($q) => $q
                            ->whereHas('gradeItem', fn ($q2) => $q2->where('subject_id', $fg->subject_id))
                            ->with('gradeItem'),
                        'attendanceRecords' => fn ($q) => $q->where('subject_id', $fg->subject_id),
                    ]);

                    $new = GradeCalculator::applyInc(
                        GradeCalculator::summary($enrollment, $config, $cutoff),
                        GradeCalculator::incompletePeriods($enrollment, $config, $cutoff)
                    );

                    // Same mapping as GradeController::submitForVerification.
                    $target = [
                        'midterm_percentage' => $new['midterm_percentage'] ?? 0,
                        'midterm_numerical'  => $new['midterm_numerical'],
                        'final_percentage'   => $new['final_percentage'] ?? 0,
                        'final_numerical'    => $new['final_numerical'],
                        'average_numerical'  => $new['average_numerical'],
                        'final_grade'        => $new['final_grade'] ?? 0,
                        'numerical_grade'    => $new['numerical_grade'],
                        'letter_grade'       => $new['letter_grade'],
                        'remarks'            => $new['remarks'],
                    ];

                    $changes = [];
                    foreach ($target as $field => $value) {
                        $old = $this->normalize($fg->{$field});
                        $now = $this->normalize($value);
                        if ($old !== $now) {
                            $changes[] = $field . ' ' . ($old ?? 'null') . ' -> ' . ($now ?? 'null');
                        }
                    }

                    if (!$changes) {
                        continue;
                    }

                    $status = $verifications->get($term->id . '-' . $fg->subject_id)?->status ?? 'none';
                    $held   = $status === 'verified' && !$includeVerified;

                    $report[] = [$label, $status . ($held ? ' (kept)' : ''), implode('; ', $changes)];

                    if ($held) {
                        $heldVerified++;
                    } else {
                        $updates[$fg->id] = $target;
                    }
                }
            });

        if ($report) {
            $this->table(['Student / Subject', 'Verification', 'Changes'], $report);
        }

        foreach ($skipped as $reason => $labels) {
            $this->warn(count($labels) . " skipped: {$reason} (e.g. " . implode(', ', array_slice($labels, 0, 3)) . ')');
        }

        $this->line("Checked {$checked} final grade rows; " . count($report) . ' differ from the current rules.');

        if ($heldVerified) {
            $this->line("{$heldVerified} verified row(s) are kept as they are. Use --include-verified to change them.");
        }

        if (!$apply) {
            $this->info(count($updates) . ' row(s) would be updated. Dry run only: run with --apply to write.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($updates) {
            foreach ($updates as $id => $attributes) {
                FinalGrade::whereKey($id)->update($attributes);
            }
        });

        $this->info(count($updates) . ' row(s) updated.');

        return self::SUCCESS;
    }

    private function normalize($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_numeric($value) ? number_format((float) $value, 2, '.', '') : (string) $value;
    }
}
