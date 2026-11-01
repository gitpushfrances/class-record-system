<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\SectionTerm;
use App\Services\RecordingWindow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AcademicPeriodController extends Controller
{
    private const ATTRIBUTES = [
        'start_date'          => 'semester start',
        'midterm_cutoff_date' => 'midterm cutoff',
        'finals_cutoff_date'  => 'finals cutoff',
    ];

    public function index()
    {
        $periods = AcademicPeriod::orderBy('school_year', 'asc')
            ->orderByRaw("CASE semester WHEN '1st Semester' THEN 1 WHEN '2nd Semester' THEN 2 WHEN 'Summer' THEN 3 END ASC")
            ->get();
        $active  = AcademicPeriod::getActive();
        return view('admin.academic.index', compact('periods', 'active'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'school_year' => [
                'bail', 'required', 'string', 'regex:/^\d{4}-\d{4}$/',
                function (string $attribute, mixed $value, \Closure $fail) {
                    [$from, $to] = array_map('intval', explode('-', $value));
                    if ($to !== $from + 1) {
                        $fail('The school year must be two consecutive years, for example 2025-2026.');
                    }
                },
            ],
            'semester' => 'required|in:1st Semester,2nd Semester,Summer',
        ]);

        $request->validate($this->dateRules($request->school_year), $this->dateMessages($request->school_year), self::ATTRIBUTES);

        // Prevent duplicate school_year + semester
        $exists = AcademicPeriod::where('school_year', $request->school_year)
            ->where('semester', $request->semester)
            ->exists();
        if ($exists) {
            return redirect()->route('admin.academic.index')
                ->with('error', 'This semester for that school year already exists.')
                ->withInput();
        }

        // A semester is over once its finals cutoff has passed, whichever semester or school year it is.
        if (Carbon::parse($request->finals_cutoff_date)->toDateString() < RecordingWindow::today()) {
            return redirect()->route('admin.academic.index')
                ->with('error', 'Cannot add a semester that has already ended.')
                ->withInput();
        }

        AcademicPeriod::create([
            'school_year'         => $request->school_year,
            'semester'            => $request->semester,
            'start_date'          => $request->start_date,
            'midterm_cutoff_date' => $request->midterm_cutoff_date,
            'finals_cutoff_date'  => $request->finals_cutoff_date,
            'is_active'           => false,
        ]);

        return redirect()->route('admin.academic.index')->with('success', 'Academic period added. It stays inactive until you press Set Active.');
    }

    /** Correct the dates of a period (also fills in the start date of a period created before it existed). */
    public function update(Request $request, AcademicPeriod $period)
    {
        $data = $request->validate($this->dateRules($period->school_year), $this->dateMessages($period->school_year), self::ATTRIBUTES);

        $period->update($data);

        return redirect()->route('admin.academic.index')->with('success', 'Period dates updated.');
    }

    public function setActive(AcademicPeriod $period)
    {
        // Teachers can only record inside a period's dates, so a period without all three cannot be the current one.
        if (!$period->start_date || !$period->midterm_cutoff_date || !$period->finals_cutoff_date) {
            return redirect()->route('admin.academic.index')
                ->with('error', 'Set the semester start, midterm cutoff and finals cutoff (Edit dates) before making this period active.');
        }

        // Deactivate all first
        AcademicPeriod::query()->update(['is_active' => false]);

        // Set this one as active
        $period->update(['is_active' => true]);

        return redirect()->route('admin.academic.index')->with('success', 'Active period updated.');
    }

    public function destroy(AcademicPeriod $period)
    {
        if ($period->is_active) {
            return redirect()->route('admin.academic.index')->with('error', 'Cannot delete the active period.');
        }

        // The dates of an active class term come from its period: deleting it would close recording for that class.
        $inUse = SectionTerm::active()
            ->where('academic_year', $period->school_year)
            ->where('semester', $period->semester)
            ->count();
        if ($inUse > 0) {
            return redirect()->route('admin.academic.index')
                ->with('error', "Cannot delete this period: {$inUse} active class " . Str::plural('term', $inUse) . ' still use it.');
        }

        $period->delete();
        return redirect()->route('admin.academic.index')->with('success', 'Period deleted.');
    }

    /** The three dates, in order, all inside the school year named by the label (January 1 of its first year to December 31 of its second). */
    private function dateRules(string $schoolYear): array
    {
        [$from, $to] = array_map('intval', explode('-', $schoolYear));
        $inside = ["after_or_equal:{$from}-01-01", "before_or_equal:{$to}-12-31"];

        return [
            'start_date'          => array_merge(['required', 'date'], $inside),
            'midterm_cutoff_date' => array_merge(['required', 'date', 'after:start_date'], $inside),
            'finals_cutoff_date'  => array_merge(['required', 'date', 'after:midterm_cutoff_date'], $inside),
        ];
    }

    private function dateMessages(string $schoolYear): array
    {
        [$from, $to] = explode('-', $schoolYear);
        $inside = "The :attribute must fall inside school year {$schoolYear} (January {$from} to December {$to}).";

        return [
            'after'           => 'The :attribute must be after the :date.',
            'after_or_equal'  => $inside,
            'before_or_equal' => $inside,
        ];
    }
}
