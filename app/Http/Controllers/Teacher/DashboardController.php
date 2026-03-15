<?php
namespace App\Http\Controllers\Teacher;
use App\Http\Controllers\Controller;
use App\Models\SectionTerm;
class DashboardController extends Controller
{
    public function index()
    {
        $teacher = auth()->user();

        $sectionTerms = SectionTerm::where('adviser_id', $teacher->id)
            ->where('status', 'active')
            ->with([
                'section.program.department',
                'enrollments',
            ])
            ->get();

        return view('teacher.dashboard', compact('sectionTerms'));
    }
}
