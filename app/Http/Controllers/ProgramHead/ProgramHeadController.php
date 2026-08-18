<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\FinalGrade;
use App\Models\GradeVerification;
use App\Models\SectionTerm;
use App\Models\Subject;
use Illuminate\Http\Request;

class ProgramHeadController extends Controller
{
    public function dashboard()
    {
        $sectionTerms = SectionTerm::with([
            'section.program',
            'adviser',
            'verifications.verifiedBy',
            'subjects',
            'enrollments.student',
        ])
        ->where('status', 'active')
        ->get();

        $enrollmentIds = $sectionTerms->pluck('enrollments')->flatten()->pluck('id');

        $finalGrades = FinalGrade::whereIn('enrollment_id', $enrollmentIds)
            ->get()
            ->groupBy(fn($fg) => $fg->enrollment_id . '-' . $fg->subject_id);

        return view('program-head.dashboard', compact('sectionTerms', 'finalGrades'));
    }

    public function verify(Request $request, SectionTerm $sectionTerm, Subject $subject)
    {
        $verification = $sectionTerm->verificationFor($subject->id);
        abort_if(!$verification || $verification->status !== 'pending', 404, 'No pending submission found for this subject.');

        $verification->update([
            'status'           => 'verified',
            'verified_by'      => auth()->id(),
            'verified_at'      => now(),
            'rejection_reason' => null,
            'notes'            => $request->input('notes'),
        ]);

        return back()->with('success', 'Grades verified successfully.');
    }

    public function reject(Request $request, SectionTerm $sectionTerm, Subject $subject)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $verification = $sectionTerm->verificationFor($subject->id);
        abort_if(!$verification || $verification->status !== 'pending', 404, 'No pending submission found for this subject.');

        $verification->update([
            'status'           => 'rejected',
            'verified_by'      => auth()->id(),
            'verified_at'      => now(),
            'rejection_reason' => $request->input('reason'),
        ]);

        $section = $sectionTerm->section;
        $section->gradeItemsFor($subject->id)->update(['is_locked' => false]);

        $enrollmentIds = $sectionTerm->enrollments()->pluck('id');
        FinalGrade::where('subject_id', $subject->id)
            ->whereIn('enrollment_id', $enrollmentIds)
            ->update(['is_locked' => false, 'locked_at' => null]);

        return back()->with('success', 'Submission rejected and sent back to the teacher.');
    }

    public function unverify(SectionTerm $sectionTerm, Subject $subject)
    {
        $verification = $sectionTerm->verificationFor($subject->id);
        abort_if(!$verification || $verification->status !== 'verified', 404, 'This subject is not currently verified.');

        $verification->update([
            'status'      => 'pending',
            'verified_by' => null,
            'verified_at' => null,
        ]);

        return back()->with('success', 'Verification removed. Submission returned to pending.');
    }
}
