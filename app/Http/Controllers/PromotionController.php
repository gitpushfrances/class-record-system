<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\Program;
use App\Models\Promotion;
use App\Models\Section;
use App\Services\BatchPromotion;
use App\Services\Scope;
use Illuminate\Http\Request;

/**
 * Batch year-level update, shared by the Dean (department → program → year →
 * section) and the Program Head (own program → year → section).
 */
class PromotionController extends Controller
{
    public function index()
    {
        if (Scope::role() === 'program_head') {
            return redirect()->route('program-head.promotions.program', Scope::programId());
        }

        $programs = Program::where('department_id', Scope::departmentId())
            ->where('status', 'approved')
            ->withCount(['sections' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('code')
            ->get();

        return view('promotions.index', ['programs' => $programs, 'prefix' => $this->prefix()]);
    }

    public function program(Program $program)
    {
        $this->authorizeProgram($program);

        $sections = Section::with('terms')
            ->where('program_id', $program->id)
            ->where('status', 'active')
            ->orderBy('year_number')
            ->orderBy('section_letter')
            ->get()
            ->groupBy('year_level');

        return view('promotions.program', [
            'program'  => $program,
            'sections' => $sections,
            'period'   => AcademicPeriod::getActive(),
            'prefix'   => $this->prefix(),
        ]);
    }

    public function section(Section $section)
    {
        $section->load('program');
        $this->authorizeProgram($section->program);

        $source = BatchPromotion::sourceTerm($section);

        return view('promotions.section', [
            'section'    => $section,
            'source'     => $source,
            'targets'    => $source ? BatchPromotion::targetTerms($section) : collect(),
            'candidates' => $source ? BatchPromotion::candidates($source) : collect(),
            'history'    => $source ? Promotion::with(['targetTerm.section', 'promoter'])->where('source_section_term_id', $source->id)->latest()->get() : collect(),
            'period'     => AcademicPeriod::getActive(),
            'prefix'     => $this->prefix(),
        ]);
    }

    public function store(Request $request, Section $section)
    {
        $section->load('program');
        $this->authorizeProgram($section->program);

        $source = BatchPromotion::sourceTerm($section);
        abort_if(!$source, 422, 'This section has no closed term to promote from.');

        $data = $request->validate([
            'target_term_id' => 'required|integer',
            'student_ids'    => 'required|array|min:1',
            'student_ids.*'  => 'integer',
        ], ['student_ids.required' => 'Tick at least one student to promote.']);

        $target = BatchPromotion::targetTerms($section)->firstWhere('id', (int) $data['target_term_id']);
        abort_if(!$target, 422, 'The target section has no term for the active academic period.');

        $result  = BatchPromotion::promote($source, $target, $data['student_ids']);
        $label   = "{$target->section->program->code} {$target->section->year_number}-{$target->section->section_letter}";
        $message = "{$result['promoted']} " . \Illuminate\Support\Str::plural('student', $result['promoted']) . " moved to {$label} ({$target->section->year_level}).";
        if ($result['skipped']) {
            $message .= ' Already enrolled there, skipped: ' . implode(', ', $result['skipped']) . '.';
        }

        return redirect()->route("{$this->prefix()}.promotions.section", $section)->with('success', $message);
    }

    private function authorizeProgram(Program $program): void
    {
        $allowed = Scope::role() === 'program_head'
            ? (int) $program->id === Scope::programId()
            : (int) $program->department_id === Scope::departmentId();

        abort_unless($allowed, 403, 'This program is outside your department or program.');
    }

    private function prefix(): string
    {
        return Scope::role() === 'dean' ? 'dean' : 'program-head';
    }
}
