<?php

namespace App\Exports;

use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Subject;
use Illuminate\Support\Collection;
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
    protected ?SectionTerm $currentTerm;
    protected Subject $subject;
    protected array $matrix;
    protected Collection $enrollments;
    protected array $liveGrades;

    const HEADER_COLOR = 'FF1E3A5F';
    const SUBHEADER_COLOR = 'FF2D6A9F';
    const AVERAGE_COLOR = 'FFFFF3CD';

    // ARGB fills matched to the same component colors used on screen (by position in matrix)
    const FILLS = ['FFBFDBFF', 'FFE9D5FF', 'FFD1FAE5', 'FFFDE68A', 'FFCCFBF4', 'FFFECDD3'];

    public function __construct(
        Section $section,
        ?SectionTerm $currentTerm,
        Subject $subject,
        array $matrix,
        Collection $enrollments,
        array $liveGrades
    ) {
        $this->section     = $section;
        $this->currentTerm = $currentTerm;
        $this->subject     = $subject;
        $this->matrix      = $matrix;
        $this->enrollments = $enrollments;
        $this->liveGrades  = $liveGrades;
    }

    public function title(): string
    {
        return 'Class Record';
    }

    public function array(): array
    {
        $section = $this->section;
        $matrix  = $this->matrix;

        $rows = [];
        $rows[] = ['CLASS RECORD'];
        $rows[] = ['Subject: ' . $this->subject->code . ' — ' . $this->subject->name];
        $rows[] = ['Section: ' . $section->program->code . ' ' . $section->year_number . '-' . $section->section_letter . '   |   Year Level: ' . $section->year_level];
        $rows[] = [
            $this->currentTerm
                ? $this->currentTerm->semester . '   |   A.Y. ' . $this->currentTerm->academic_year
                : 'No active term'
        ];
        $rows[] = [];

        // Group header row
        $groupRow = ['', '', ''];
        foreach ($matrix as $comp) {
            if ($comp['type'] === 'items') {
                $count = $comp['items']->count();
                $groupRow[] = strtoupper($comp['label']) . ' (' . $comp['weight'] . '%)';
                for ($i = 1; $i < $count; $i++) $groupRow[] = '';
                $groupRow[] = 'WEIGHTED';
            } else {
                $groupRow[] = strtoupper($comp['label']) . ' (' . $comp['weight'] . '%)';
                $groupRow[] = 'WEIGHTED';
            }
        }
        $groupRow[] = 'FINAL %';
        $groupRow[] = 'GRADE';
        $groupRow[] = 'REMARKS';
        $rows[] = $groupRow;

        // Sub-header row
        $subRow = ['#', 'Student No.', 'Name'];
        foreach ($matrix as $comp) {
            if ($comp['type'] === 'items') {
                foreach ($comp['items'] as $item) {
                    $subRow[] = $item->name . "\n(" . $item->max_score . ')';
                }
                $subRow[] = 'Score';
            } else {
                $subRow[] = 'Days Present';
                $subRow[] = 'Score';
            }
        }
        $subRow[] = '';
        $subRow[] = '';
        $subRow[] = '';
        $rows[] = $subRow;

        // Data rows
        $counter      = 1;
        $columnSums   = [];
        $studentCount = 0;
        $cutoffDate   = $this->currentTerm?->midterm_cutoff_date;

        foreach ($this->enrollments as $enrollment) {
            $student  = $enrollment->student;
            $lg       = $this->liveGrades[$enrollment->id] ?? null;
            $gradeMap = $enrollment->studentGrades->keyBy('grade_item_id');

            $row = [
                $counter++,
                $student->student_number,
                $student->last_name . ', ' . $student->first_name . ($student->middle_name ? ' ' . substr($student->middle_name, 0, 1) . '.' : ''),
            ];

            $colIndex = 3;
            foreach ($matrix as $comp) {
                if ($comp['type'] === 'items') {
                    foreach ($comp['items'] as $item) {
                        $score = $gradeMap->get($item->id)?->score ?? '';
                        $row[] = $score !== '' ? (float) $score : '';
                        $columnSums[$colIndex] = ($columnSums[$colIndex] ?? 0) + (float) $score;
                        $colIndex++;
                    }
                    $weighted = $lg ? round($lg['scores'][$comp['key']] ?? 0, 2) : '';
                    $row[] = $weighted;
                    $columnSums[$colIndex] = ($columnSums[$colIndex] ?? 0) + (float) $weighted;
                    $colIndex++;
                } else {
                    $period  = $comp['period'];
                    $records = $enrollment->attendanceRecords->filter(
                        fn($r) => $cutoffDate
                            ? ($period === 'midterm' ? $r->date->lte($cutoffDate) : $r->date->gt($cutoffDate))
                            : false
                    );
                    $total   = $records->count();
                    $present = $records->whereIn('status', ['present', 'late'])->count();
                    $row[] = $total > 0 ? $present . '/' . $total : '—';
                    $colIndex++;

                    $weighted = $lg ? round($lg['scores'][$comp['key']] ?? 0, 2) : '';
                    $row[] = $weighted;
                    $columnSums[$colIndex] = ($columnSums[$colIndex] ?? 0) + (float) $weighted;
                    $colIndex++;
                }
            }

            $row[] = $lg ? round($lg['final_grade'], 2) : '';
            $row[] = $lg ? number_format($lg['numerical_grade'], 2) : '';
            $row[] = $lg ? ucfirst($lg['remarks']) : '';

            $columnSums[$colIndex] = ($columnSums[$colIndex] ?? 0) + (float) ($lg['final_grade'] ?? 0);

            $rows[] = $row;
            $studentCount++;
        }

        // Average row
        $avgRow = ['', '', 'CLASS AVERAGE'];
        $colIndex = 3;
        foreach ($matrix as $comp) {
            if ($comp['type'] === 'items') {
                foreach ($comp['items'] as $item) {
                    $avgRow[] = $studentCount > 0 ? round(($columnSums[$colIndex] ?? 0) / $studentCount, 2) : '';
                    $colIndex++;
                }
                $avgRow[] = $studentCount > 0 ? round(($columnSums[$colIndex] ?? 0) / $studentCount, 2) : '';
                $colIndex++;
            } else {
                $avgRow[] = '—';
                $colIndex++;
                $avgRow[] = $studentCount > 0 ? round(($columnSums[$colIndex] ?? 0) / $studentCount, 2) : '';
                $colIndex++;
            }
        }
        $avgRow[] = $studentCount > 0 ? round(($columnSums[$colIndex] ?? 0) / $studentCount, 2) : '';
        $avgRow[] = '';
        $avgRow[] = '';
        $rows[] = $avgRow;

        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 5, 'B' => 14, 'C' => 28];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = $sheet->getHighestColumn();

        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->mergeCells('A2:' . $lastCol . '2');
        $sheet->mergeCells('A3:' . $lastCol . '3');
        $sheet->mergeCells('A4:' . $lastCol . '4');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:A4')->getFont()->setSize(10);

        $sheet->getStyle('A6:' . $lastCol . '6')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_COLOR]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);

        $sheet->getStyle('A7:' . $lastCol . '7')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::SUBHEADER_COLOR]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);

        if ($lastRow > 8) {
            $sheet->getStyle('A8:' . $lastCol . ($lastRow - 1))->applyFromArray([
                'font' => ['bold' => false, 'color' => ['rgb' => '000000']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
            ]);
        }

        $sheet->getStyle('A' . $lastRow . ':' . $lastCol . $lastRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::AVERAGE_COLOR]],
        ]);

        $sheet->getStyle('A6:' . $lastCol . $lastRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFCCCCCC']]],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(6)->setRowHeight(30);
        $sheet->getRowDimension(7)->setRowHeight(35);

        foreach (range('D', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}
