<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Program;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $departmentId = \App\Services\Scope::departmentId();

        $sort      = in_array($request->get('sort'), ['last_name', 'student_number', 'created_at']) ? $request->get('sort') : 'last_name';
        $direction = $request->get('direction') === 'desc' ? 'desc' : 'asc';

        $programs = Program::where('department_id', $departmentId)
            ->where('status', 'approved')
            ->orderBy('code')
            ->get();

        $students = Student::with('program')
            ->whereHas('program', fn($q) => $q->where('department_id', $departmentId))
            ->when($request->filled('program_id'), fn($q) => $q->where('program_id', $request->get('program_id')))
            ->when($request->filled('search'), fn ($q) => \App\Services\NameSearch::apply($q, $request->get('search'), ['last_name', 'first_name'], 'student_number', 'email'))
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->appends($request->query());

        return view('dean.students.index', compact('students', 'programs', 'sort', 'direction'));
    }
}
