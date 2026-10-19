<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'section_id',
        'section_term_id',
        'subject_id',
        'component_type',
        'period',
        'name',
        'max_score',
        'date_given',
        'description',
        'is_locked',
        'created_by',
    ];

    protected $casts = [
        'max_score'  => 'decimal:2',
        'date_given' => 'date',
        'is_locked'  => 'boolean',
    ];

    /**
     * Name for the next item of a component: "Quiz 3" (highest existing number + 1).
     * An exam component (key starting with "exam") holds one item per period:
     * "Midterm Examination" or "Final Examination"; null once it exists.
     */
    public static function autoName($existing, string $key, string $label, string $period): ?string
    {
        if (str_starts_with($key, 'exam')) {
            return $existing->isEmpty() ? ($period === 'midterm' ? 'Midterm Examination' : 'Final Examination') : null;
        }

        $label = trim($label) !== '' ? trim($label) : 'Item';
        $last  = $existing
            ->map(fn ($item) => preg_match('/^' . preg_quote($label, '/') . ' (\d+)$/', $item->name, $m) ? (int) $m[1] : 0)
            ->max() ?? 0;

        return $label . ' ' . ($last + 1);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function sectionTerm()
    {
        return $this->belongsTo(SectionTerm::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function studentGrades()
    {
        return $this->hasMany(StudentGrade::class);
    }
}
