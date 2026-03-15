<?php
$file = 'app/Http/Controllers/Teacher/GradeController.php';
$content = file_get_contents($file);

$newMethod = '    private function calculateComponentScores($enrollment, $config): array
    {
        $scores = [
            \'quiz\'       => 0,
            \'exam\'       => 0,
            \'project\'    => 0,
            \'assessment\' => 0,
            \'attendance\' => 0,
        ];

        $activeWeight = 0;

        foreach ([\'quiz\', \'exam\', \'project\', \'assessment\'] as $type) {
            $weight = (float) $config->{$type . \'_weight\'};
            if ($weight === 0.0) continue;

            $items = $enrollment->studentGrades->filter(
                fn($g) => $g->gradeItem !== null && $g->gradeItem->component_type === $type
            );

            if ($items->isNotEmpty()) {
                $earned   = $items->sum(fn($g) => (float) $g->score);
                $possible = $items->sum(fn($g) => (float) $g->gradeItem->max_score);

                $scores[$type] = $possible > 0
                    ? round(($earned / $possible) * $weight, 2)
                    : 0;

                $activeWeight += $weight;
            }
        }

        $attendanceWeight = (float) $config->attendance_weight;
        if ($attendanceWeight > 0) {
            $total   = $enrollment->attendanceRecords->count();
            $present = $enrollment->attendanceRecords->whereIn(\'status\', [\'present\', \'late\'])->count();

            if ($total > 0) {
                $scores[\'attendance\'] = round(($present / $total) * $attendanceWeight, 2);
                $activeWeight += $attendanceWeight;
            }
        }

        if ($activeWeight > 0 && $activeWeight < 100) {
            $factor = 100 / $activeWeight;
            foreach ($scores as $key => $val) {
                $scores[$key] = round($val * $factor, 2);
            }
        }

        return $scores;
    }';

$content = preg_replace(
    '/private function calculateComponentScores\(\$enrollment, \$config\): array.+?return \$scores;\n    \}/s',
    $newMethod,
    $content
);

file_put_contents($file, $content);
echo "Done\n";
