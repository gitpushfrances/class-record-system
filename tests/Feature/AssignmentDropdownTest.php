<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\Program;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use App\Services\TermRollover;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** On the Dean's Assignments page the sections are listed program by program, and a section's subjects follow its year level. */
class AssignmentDropdownTest extends TestCase
{
    use RefreshDatabase;

    private const YEARS = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];

    private User $dean;
    private User $teacher;
    private AcademicPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $seeded        = SectionTerm::where('status', 'active')->whereHas('section.program', fn ($q) => $q->where('code', 'BSCS'))->firstOrFail();
        $this->dean    = User::where('email', 'dean@classrecord.test')->firstOrFail();
        $this->teacher = User::where('email', 'teacher@classrecord.test')->firstOrFail();

        AcademicPeriod::query()->update(['is_active' => false]);
        $this->period = AcademicPeriod::create(['school_year' => $seeded->academic_year, 'semester' => $seeded->semester, 'is_active' => true]);
    }

    private function section(string $program, int $year, string $letter): Section
    {
        $section = Section::create([
            'program_id'     => Program::where('code', $program)->value('id'),
            'year_number'    => (string) $year,
            'section_letter' => $letter,
            'year_level'     => self::YEARS[$year],
            'status'         => 'active',
        ]);
        TermRollover::setTerm($section, $this->teacher->id, $this->period);

        return $section;
    }

    private function subject(string $code, ?string $yearLevel): Subject
    {
        return Subject::create([
            'code' => $code, 'name' => "Subject {$code}", 'units' => 3, 'status' => 'approved', 'has_laboratory' => false,
            'program_id' => Program::where('code', 'BSCS')->value('id'),
            'year_level' => $yearLevel, 'semester' => $this->period->semester,
        ]);
    }

    public function test_sections_are_listed_by_program_then_year_then_letter(): void
    {
        $this->section('BSIT', 2, 'A');
        $this->section('BSCS', 2, 'A');
        $this->section('BSCS', 1, 'B');
        $this->section('BSIT', 1, 'A');
        $this->section('BSCS', 1, 'A');

        $this->actingAs($this->dean)->get(route('dean.assignments.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'BSCS 1-A</option>', 'BSCS 1-B</option>', 'BSCS 2-A</option>', 'BSCS 3-A</option>', 'BSIT 1-A</option>', 'BSIT 2-A</option>',
            ], false);
    }

    public function test_sections_and_subjects_carry_their_year_level_for_the_filter(): void
    {
        $first  = $this->section('BSCS', 1, 'A');
        $second = $this->subject('FIL-1', '1st Year');
        $third  = $this->subject('FIL-2', '2nd Year');

        $this->actingAs($this->dean)->get(route('dean.assignments.index'))
            ->assertOk()
            ->assertSee('id="assign-section"', false)
            ->assertSee('id="assign-subject"', false)
            ->assertSee('value="' . $first->id . '" data-year="1st Year"', false)
            ->assertSee('value="' . $second->id . '" data-lab="0" data-year="1st Year"', false)
            ->assertSee('value="' . $third->id . '" data-lab="0" data-year="2nd Year"', false)
            ->assertSee('Select a section first');
    }

    public function test_a_subject_without_a_year_level_is_still_offered(): void
    {
        $unset = $this->subject('FIL-3', null);

        $this->actingAs($this->dean)->get(route('dean.assignments.index'))
            ->assertOk()
            ->assertSee('value="' . $unset->id . '" data-lab="0" data-year=""', false);
    }
}
