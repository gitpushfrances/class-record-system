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
        $stats = [
            'total_teachers' => User::where('role', 'teacher')->where('status', 'active')->count(),
            'total_sections' => Section::count(),
            'total_students' => Student::count(),
        ];
        return view('dean.dashboard', compact('stats'));
    }
}
