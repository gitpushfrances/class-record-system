<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\GradeVerification;
use App\Models\SectionTerm;
use Illuminate\Http\Request;

class ProgramHeadController extends Controller
{
    public function dashboard()
    {
        $sectionTerms = SectionTerm::with([
            'section.program',
            'adviser',
            'verification.verifiedBy',
            'enrollments.finalGrade',
        ])
        ->where('status', 'active')
        ->get();

        return view('program-head.dashboard', compact('sectionTerms'));
    }

    public function verify(Request $request, SectionTerm $sectionTerm)
    {
        if ($sectionTerm->verification) {
            return back()->with('error', 'This section term is already verified.');
        }

        GradeVerification::create([
            'section_term_id' => $sectionTerm->id,
            'verified_by'     => auth()->id(),
            'verified_at'     => now(),
            'notes'           => $request->input('notes'),
        ]);

        return back()->with('success', 'Grades verified successfully.');
    }

    public function unverify(SectionTerm $sectionTerm)
    {
        if (!$sectionTerm->verification) {
            return back()->with('error', 'This section term has no verification to remove.');
        }

        $sectionTerm->verification->delete();

        return back()->with('success', 'Verification removed.');
    }
}
