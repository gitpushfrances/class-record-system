<?php
namespace App\Http\Controllers\Dean;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
class DashboardController extends Controller
{
    public function index()
    {
        $departmentId = auth()->user()->department_id;

        $stats = [
            'total_teachers' => User::where('role', 'teacher')
                ->where('status', 'active')
                ->where('department_id', $departmentId)
                ->count(),
            'total_sections' => Section::whereHas('program', fn($q) => $q->where('department_id', $departmentId))->count(),
            // total_students left unscoped — see open question on student scoping.
            'total_students' => Student::count(),
        ];
        return view('dean.dashboard', compact('stats'));
    }
}
