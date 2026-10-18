<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\SectionTerm;
use App\Models\User;
use App\Services\Scope;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dean faculty management: deactivate teachers who no longer teach in the
 * department, or reactivate them. Accounts are never deleted, because grades,
 * verifications and ROG signatures reference them.
 */
class FacultyController extends Controller
{
    public function index(Request $request)
    {
        $departmentId = Scope::departmentId();
        $status       = $request->get('status') === 'inactive' ? 'inactive' : 'active';

        $teachers = User::where('role', 'teacher')
            ->where('department_id', $departmentId)
            ->where('status', $status)
            ->when($request->filled('search'), fn ($q) => \App\Services\NameSearch::apply($q, $request->get('search'), ['last_name', 'first_name', 'name'], null, 'email'))
            ->orderBy('name')
            ->paginate(25)
            ->appends($request->query());

        $load   = self::loadFor($teachers->pluck('id'));
        $counts = User::where('role', 'teacher')
            ->where('department_id', $departmentId)
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('dean.faculty.index', compact('teachers', 'status', 'load', 'counts'));
    }

    public function deactivate(User $user)
    {
        $this->authorizeTeacher($user);

        $load = self::loadFor(collect([$user->id]))[$user->id];
        if ($load['subjects'] > 0 || $load['advisory'] > 0) {
            return back()->with('error', "{$user->name} still has {$load['subjects']} subject(s) and {$load['advisory']} advisory section(s) this term. Reassign them first, then deactivate.");
        }

        $user->update(['status' => 'inactive']);

        return back()->with('success', "{$user->name} deactivated. They can no longer sign in; their records are kept.");
    }

    public function activate(User $user)
    {
        $this->authorizeTeacher($user);
        abort_if($user->status !== 'inactive', 422, 'Only inactive teachers can be reactivated.');

        $user->update(['status' => 'active']);

        return back()->with('success', "{$user->name} reactivated.");
    }

    private function authorizeTeacher(User $user): void
    {
        abort_if($user->role !== 'teacher', 404);
        abort_if((int) $user->department_id !== Scope::departmentId(), 403, 'This teacher does not belong to your department.');
    }

    /** Subjects taught and sections advised in active terms, per teacher id. */
    private static function loadFor(Collection $ids): array
    {
        $subjects = DB::table('section_subject_teachers')
            ->join('section_terms', 'section_terms.id', '=', 'section_subject_teachers.section_term_id')
            ->where('section_terms.status', 'active')
            ->whereIn('section_subject_teachers.teacher_id', $ids)
            ->selectRaw('section_subject_teachers.teacher_id AS id, COUNT(*) AS total')
            ->groupBy('section_subject_teachers.teacher_id')
            ->pluck('total', 'id');

        $advisory = SectionTerm::where('status', 'active')
            ->whereIn('adviser_id', $ids)
            ->selectRaw('adviser_id AS id, COUNT(*) AS total')
            ->groupBy('adviser_id')
            ->pluck('total', 'id');

        return $ids->mapWithKeys(fn ($id) => [$id => [
            'subjects' => (int) ($subjects[$id] ?? 0),
            'advisory' => (int) ($advisory[$id] ?? 0),
        ]])->all();
    }
}
