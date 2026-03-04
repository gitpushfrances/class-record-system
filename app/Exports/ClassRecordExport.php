<?php

namespace App\Exports;

use App\Models\FinalGrade;
use App\Models\Section;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ClassRecordExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    protected Section $section;
    protected array $liveGrades;

    // Component colors (ARGB)
    const COLORS = [
        'quiz'       => 'FFBFDBFF', // blue
        'exam'       => 'FFE9D5FF', // purple
        'project'    => 'FFD1FAE5', // green
        'assessment' => 'FFFDE68A', // orange
        'attendance' => 'FFCCFBF4', // teal
        'header'     => 'FF1E3A5F', // dark navy
        'subheader'  => 'FF2D6A9F', // medium blue
        'average'    => 'FFFFF3CD', // light yellow
    ];

    public function __construct(Section $section, array $liveGrades)
    {
        $this->section    = $section;
        $this->liveGrades = $liveGrades;
    }

    public function title(): string
    {
        return 'Class Record';
    }

    public function array(): array
    {
        $section    = $this->section;
        $config     = $section->gradeConfiguration;
        $gradeItems = $section->gradeItems->sortBy('created_at')->groupBy('component_type');
        $enrollments = $section->enrollments;

        $components = ['quiz', 'exam', 'project', 'assessment'];

        // ── Row 1-5: Header block ──────────────────────────────────────────
        $rows = [];
        $rows[] = ['CLASS RECORD'];
        $rows[] = ['Subject: ' . $section->subject->code . ' — ' . $section->subject->name];
        $rows[] = ['Section: ' . $section->section_name . '   |   Year Level: ' . $section->year_level];
        $rows[] = ['Teacher: ' . $section->teacher->name . '   |   ' . $section->semester . '   |   A.Y. ' . $section->academic_year];
        $rows[] = [];

        // ── Build column map ───────────────────────────────────────────────
        // Fixed: No. | Student No. | Name
        // Then per component: [items...] | Weighted Score
        // Then: Attendance | Final % | Numerical Grade | Remarks
        $componentColumns = [];
        foreach ($components as $comp) {
            $items = $gradeItems->get($comp, collect());
            $componentColumns[$comp] = $items;
        }

        // ── Row 6: Component group headers ────────────────────────────────
        $groupRow = ['', '', ''];
        foreach ($components as $comp) {
            $items = $componentColumns[$comp];
            $count = $items->count();
            if ($count > 0) {
                $groupRow[] = strtoupper($comp) . ' (' . $config->{$comp . '_weight'} . '%)';
                for ($i = 1; $i < $count; $i++) $groupRow[] = '';
                $groupRow[] = 'WEIGHTED';
            }
        }
        $groupRow[] = 'ATTENDANCE (' . $config->attendance_weight . '%)';
        $groupRow[] = 'FINAL %';
        $groupRow[] = 'GRADE';
        $groupRow[] = 'REMARKS';
        $rows[] = $groupRow;

        // ── Row 7: Column sub-headers ──────────────────────────────────────
        $subRow = ['#', 'Student No.', 'Name'];
        foreach ($components as $comp) {
            $items = $componentColumns[$comp];
            if ($items->count() > 0) {
                foreach ($items as $item) {
                    $subRow[] = $item->name . "\n(" . $item->max_score . ')';
                }
                $subRow[] = 'Score';
            }
        }
        $subRow[] = 'Score';
        $subRow[] = '';
        $subRow[] = '';
        $subRow[] = '';
        $rows[] = $subRow;

        // ── Data rows ─────────────────────────────────────────────────────
        $counter        = 1;
        $columnSums     = [];
        $studentCount   = 0;

        foreach ($enrollments as $enrollment) {
            $student    = $enrollment->student;
            $grades     = $this->liveGrades[$enrollment->id] ?? null;
            $gradeMap   = $enrollment->studentGrades->keyBy('grade_item_id');
            $attendRec  = $enrollment->attendanceRecords;

            $row = [
                $counter++,
                $student->student_number,
                $student->last_name . ', ' . $student->first_name . ($student->middle_name ? ' ' . substr($student->middle_name, 0, 1) . '.' : ''),
            ];

            $colIndex = 3;
            foreach ($components as $comp) {
                $items = $componentColumns[$comp];
                if ($items->count() > 0) {
                    foreach ($items as $item) {
                        $score = $gradeMap->get($item->id)?->score ?? '';
                        $row[] = $score !== '' ? (float) $score : '';
                        $columnSums[$colIndex] = ($columnSums[$colIndex] ?? 0) + (float) $score;
                        $colIndex++;
                    }
                    $weighted = $grades ? round($grades[$comp . '_score'], 2) : '';
                    $row[] = $weighted;
                    $columnSums[$colIndex] = ($columnSums[$colIndex] ?? 0) + (float) $weighted;
                    $colIndex++;
                }
            }

            // Attendance
            $total   = $attendRec->count();
            $present = $attendRec->whereIn('status', ['present', 'late'])->count();
            $row[]   = $total > 0 ? $present . '/' . $total : '—';
            $row[]   = $grades ? round($grades['final_grade'], 2) : '';
            $row[]   = $grades ? number_format($grades['numerical_grade'], 2) : '';
            $row[]   = $grades ? ucfirst($grades['remarks']) : '';

            $columnSums[$colIndex] = ($columnSums[$colIndex] ?? 0) + (float) ($grades['attendance_score'] ?? 0);
            $colIndex++;
            $columnSums[$colIndex] = ($columnSums[$colIndex] ?? 0) + (float) ($grades['final_grade'] ?? 0);

            $rows[]       = $row;
            $studentCount++;
        }

        // ── Average row ────────────────────────────────────────────────────
        $avgRow = ['', '', 'CLASS AVERAGE'];
        $colIndex = 3;
        foreach ($components as $comp) {
            $items = $componentColumns[$comp];
            if ($items->count() > 0) {
                foreach ($items as $item) {
                    $avgRow[] = $studentCount > 0
                        ? round(($columnSums[$colIndex] ?? 0) / $studentCount, 2)
                        : '';
                    $colIndex++;
                }
                $avgRow[] = $studentCount > 0
                    ? round(($columnSums[$colIndex] ?? 0) / $studentCount, 2)
                    : '';
                $colIndex++;
            }
        }
        $avgRow[] = '—';
        $avgRow[] = $studentCount > 0
            ? round(($columnSums[$colIndex] ?? 0) / $studentCount, 2)
            : '';
        $avgRow[] = '';
        $avgRow[] = '';
        $rows[]   = $avgRow;

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 14,
            'C' => 28,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = $sheet->getHighestColumn();

        // Title rows
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->mergeCells('A2:' . $lastCol . '2');
        $sheet->mergeCells('A3:' . $lastCol . '3');
        $sheet->mergeCells('A4:' . $lastCol . '4');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:A4')->getFont()->setSize(10);

        // Header row (row 6)
        $sheet->getStyle('A6:' . $lastCol . '6')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::COLORS['header']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);

        // Sub-header row (row 7)
        $sheet->getStyle('A7:' . $lastCol . '7')->applyFromArray([
            'font'      => ['bold' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::COLORS['subheader']]],
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);

        // Average row
        $sheet->getStyle('A' . $lastRow . ':' . $lastCol . $lastRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::COLORS['average']]],
        ]);

        // All data borders
        $sheet->getStyle('A6:' . $lastCol . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'FFCCCCCC'],
                ],
            ],
        ]);

        // Row heights
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(6)->setRowHeight(30);
        $sheet->getRowDimension(7)->setRowHeight(35);

        // Auto-width for dynamic columns D onwards
        foreach (range('D', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}
