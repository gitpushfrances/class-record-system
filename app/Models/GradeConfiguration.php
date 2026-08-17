<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'section_id',
        'subject_id',
        'config_json',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'config_json' => 'array',
        'approved_at' => 'datetime',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getComponents(): array
    {
        return $this->config_json ?? [];
    }

    public function getComponentsByPeriod(string $period): array
    {
        return array_filter($this->getComponents(), fn($c) => $c['period'] === $period);
    }

    public function getWeight(string $key): float
    {
        foreach ($this->getComponents() as $c) {
            if ($c['key'] === $key) return (float) $c['weight'];
        }
        return 0.0;
    }

    public function isValidConfiguration(): bool
    {
        $midterm = array_sum(array_column(
            array_filter($this->getComponents(), fn($c) => $c['period'] === 'midterm'),
            'weight'
        ));
        $final = array_sum(array_column(
            array_filter($this->getComponents(), fn($c) => $c['period'] === 'final'),
            'weight'
        ));
        return abs($midterm - 100) < 0.01 && abs($final - 100) < 0.01;
    }

    /**
     * Palette assigned by component key (not name), so custom "Others" components
     * still get a stable color instead of falling back to nothing.
     */
    const PALETTE = [
        ['bg50' => 'bg-blue-50',   'bg100' => 'bg-blue-100',   'bg200' => 'border-blue-200',   'bg500' => 'bg-blue-500',   'border400' => 'border-blue-400',   'text' => 'text-blue-700',   'text400' => 'text-blue-400'],
        ['bg50' => 'bg-purple-50', 'bg100' => 'bg-purple-100', 'bg200' => 'border-purple-200', 'bg500' => 'bg-purple-500', 'border400' => 'border-purple-400', 'text' => 'text-purple-700', 'text400' => 'text-purple-400'],
        ['bg50' => 'bg-green-50',  'bg100' => 'bg-green-100',  'bg200' => 'border-green-200',  'bg500' => 'bg-green-500',  'border400' => 'border-green-400',  'text' => 'text-green-700',  'text400' => 'text-green-400'],
        ['bg50' => 'bg-orange-50', 'bg100' => 'bg-orange-100', 'bg200' => 'border-orange-200', 'bg500' => 'bg-orange-500', 'border400' => 'border-orange-400', 'text' => 'text-orange-700', 'text400' => 'text-orange-400'],
        ['bg50' => 'bg-teal-50',   'bg100' => 'bg-teal-100',   'bg200' => 'border-teal-200',   'bg500' => 'bg-teal-500',   'border400' => 'border-teal-400',   'text' => 'text-teal-700',   'text400' => 'text-teal-400'],
        ['bg50' => 'bg-rose-50',   'bg100' => 'bg-rose-100',   'bg200' => 'border-rose-200',   'bg500' => 'bg-rose-500',   'border400' => 'border-rose-400',   'text' => 'text-rose-700',   'text400' => 'text-rose-400'],
    ];

    /**
     * Builds an ordered list of every component (midterm first, then final),
     * each carrying its grade items (if any) and a stable color from PALETTE.
     * Used by both the live record view and the Excel export so they can
     * never drift out of sync with each other or with the config screen.
     *
     * $gradeItems must be a Collection of GradeItem already grouped by component_type,
     * e.g. Section::gradeItemsFor($subjectId)->get()->groupBy('component_type').
     */
    public function buildComponentMatrix($gradeItemsByType): array
    {
        $components = $this->getComponents();

        usort($components, fn($a, $b) => ($a['period'] === $b['period'])
            ? 0
            : ($a['period'] === 'midterm' ? -1 : 1));

        $matrix = [];
        $colorIndex = 0;

        foreach ($components as $comp) {
            $isAttendance = in_array($comp['key'], ['attendance', 'attendance_f'], true);
            $items = $gradeItemsByType->get($comp['key'], collect());

            if (!$isAttendance && $items->isEmpty()) {
                continue;
            }

            $matrix[] = [
                'key'    => $comp['key'],
                'label'  => $comp['label'],
                'weight' => (float) $comp['weight'],
                'period' => $comp['period'],
                'type'   => $isAttendance ? 'attendance' : 'items',
                'items'  => $isAttendance ? collect() : $items,
                'color'  => self::PALETTE[$colorIndex % count(self::PALETTE)],
            ];

            $colorIndex++;
        }

        return $matrix;
    }
}
