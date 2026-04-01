<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use Illuminate\Http\Request;

class AcademicPeriodController extends Controller
{
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
            'school_year' => 'required|string|max:20',
            'semester'    => 'required|in:1st Semester,2nd Semester,Summer',
        ]);

        // FIX 1 - Prevent duplicate school_year + semester
        $exists = AcademicPeriod::where('school_year', $request->school_year)
            ->where('semester', $request->semester)
            ->exists();
        if ($exists) {
            return redirect()->route('admin.academic.index')
                ->with('error', 'This semester for that school year already exists.')
                ->withInput();
        }

        // FIX 2 - Block past semesters
        $endYear = (int) explode('-', $request->school_year)[1];
        $now = \Carbon\Carbon::now();
        $currentYear = $now->year;
        $currentMonth = $now->month;

        $isPast = false;
        if ($endYear < $currentYear) {
            $isPast = true;
        } elseif ($endYear === $currentYear) {
            if ($request->semester === '1st Semester' && $currentMonth > 10) $isPast = true;
            if ($request->semester === '2nd Semester' && $currentMonth > 3) $isPast = true;
            if ($request->semester === 'Summer' && $currentMonth > 6) $isPast = true;
        }

        if ($isPast) {
            return redirect()->route('admin.academic.index')
                ->with('error', 'Cannot add a semester that has already ended.')
                ->withInput();
        }

        AcademicPeriod::create([
            'school_year' => $request->school_year,
            'semester'    => $request->semester,
            'is_active'   => false,
        ]);

        return redirect()->route('admin.academic.index')->with('success', 'Academic period added.');
    }

    public function setActive(AcademicPeriod $period)
    {
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

        $period->delete();
        return redirect()->route('admin.academic.index')->with('success', 'Period deleted.');
    }
}
