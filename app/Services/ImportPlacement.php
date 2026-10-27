<?php

namespace App\Services;

use App\Models\Program;
use App\Models\Section;
use App\Models\SectionTerm;

/**
 * Works out which section each imported student goes into (the "section" column of the import file).
 * The year comes from the student's year level; the file gives the letter ("A") or the full label ("1-A", "BSIT 1-A").
 * Read-only: it collects every problem so the whole file can be fixed in one pass, and saves nothing.
 */
class ImportPlacement
{
    private const GROUPS = [
        'format'   => ['Section not understood', 'Write the section as a letter such as A, or as 1-A.'],
        'mismatch' => ['Section does not match the year level', 'The section year must be the same as the student\'s year level.'],
        'missing'  => ['Section not found', 'Create the section first (Sections page) or correct the letter.'],
        'no_term'  => ['Section has no active term', 'Set the section\'s adviser first to start its term.'],
        'locked'   => ['Class list locked', 'Grades are already submitted. Reject those submissions first, or leave these students out.'],
    ];

    /**
     * @param  array<int, array<string, string>>  $rows  file line => validated row (with a non-empty "section")
     * @return array{terms: array<int, SectionTerm>, groups: array<int, array{title: string, hint: string, items: string[]}>}
     */
    public static function plan(int $programId, array $rows): array
    {
        if (!$rows) {
            return ['terms' => [], 'groups' => []];
        }

        $code     = Program::whereKey($programId)->value('code') ?? '';
        $sections = Section::where('program_id', $programId)->where('status', 'active')->get()
            ->keyBy(fn ($section) => $section->year_number . '-' . strtoupper($section->section_letter));
        $terms    = SectionTerm::with('section')->active()->whereIn('section_id', $sections->pluck('id'))->orderBy('id')->get()->keyBy('section_id');

        $problems = [];
        $assigned = [];
        $locked   = [];

        foreach ($rows as $line => $row) {
            $parsed = self::parse($row['section'], $code);
            if (!$parsed) {
                $problems['format'][] = "Row {$line}: \"{$row['section']}\" is not a section.";
                continue;
            }

            [$sectionYear, $letter] = $parsed;
            $year = (string) (array_search($row['year_level'], StudentImport::YEAR_LEVELS, true) + 1);
            if ($sectionYear !== null && $sectionYear !== $year) {
                $problems['mismatch'][] = "Row {$line}: section {$sectionYear}-{$letter} is for year {$sectionYear}, but the student is {$row['year_level']}.";
                continue;
            }

            $label   = trim("{$code} {$year}-{$letter}");
            $section = $sections->get("{$year}-{$letter}");
            if (!$section) {
                $problems['missing'][] = "Row {$line}: {$label} does not exist.";
                continue;
            }

            $term = $terms->get($section->id);
            if (!$term) {
                $problems['no_term'][] = "Row {$line}: {$label} has no active term yet.";
                continue;
            }

            $locked[$term->id] ??= SectionEnrollment::lockedSubjects($term);
            if ($locked[$term->id]) {
                $problems['locked'][] = "Row {$line}: {$label} is locked (grades submitted for " . implode(', ', $locked[$term->id]) . ').';
                continue;
            }

            $assigned[$line] = $term;
        }

        $groups = [];
        foreach (self::GROUPS as $key => [$title, $hint]) {
            if (!empty($problems[$key])) {
                $groups[] = ['title' => $title, 'hint' => $hint, 'items' => $problems[$key]];
            }
        }

        return ['terms' => $assigned, 'groups' => $groups];
    }

    /** [year or null, LETTER] from "A", "1-A", "1 A" or "BSIT 1-A"; null when it is none of these. */
    public static function parse(string $value, string $programCode = ''): ?array
    {
        $value = strtoupper(trim($value));
        if ($programCode !== '') {
            $value = preg_replace('/^' . preg_quote(strtoupper($programCode), '/') . '\s*/', '', $value);
        }

        return preg_match('/^(?:([1-5])\s*[-\s]?\s*)?([A-Z])$/', $value, $m) ? [$m[1] !== '' ? $m[1] : null, $m[2]] : null;
    }
}
