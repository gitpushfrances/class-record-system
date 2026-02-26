<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use Illuminate\Http\Request;

class AcademicPeriodController extends Controller
{
    public function index()
    {
        $periods = AcademicPeriod::orderBy('created_at', 'desc')->get();
        $active  = AcademicPeriod::getActive();
        return view('admin.academic.index', compact('periods', 'active'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'school_year' => 'required|string|max:20',
            'semester'    => 'required|in:1st Semester,2nd Semester,Summer',
        ]);

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
