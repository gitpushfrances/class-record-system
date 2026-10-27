<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\GradeVerification;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\ImportPlacement;
use App\Services\StudentImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Import by year level: the "section" column enrolls each new student into that section of their year.
 */
class ImportPlacementTest extends TestCase
{
    use RefreshDatabase;

    private User $programHead;
    private SectionTerm $term;
    private string $yearLevel;
    private string $letter;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
        $this->term        = SectionTerm::with('section')->where('status', 'active')
            ->whereHas('section', fn ($q) => $q->where('program_id', $this->programHead->program_id))
            ->firstOrFail();
        $this->yearLevel   = StudentImport::YEAR_LEVELS[(int) $this->term->section->year_number - 1];
        $this->letter      = $this->term->section->section_letter;
    }

    private function csv(string ...$rows): UploadedFile
    {
        $lines = array_merge(['student_number,last_name,first_name,middle_name,gender,year_level,student_type,email,section'], $rows);

        return UploadedFile::fake()->createWithContent('students.csv', implode("\n", $lines) . "\n");
    }

    private function import(string ...$rows)
    {
        return $this->actingAs($this->programHead)->post(route('program-head.students.import'), ['file' => $this->csv(...$rows)]);
    }

    private function groupTitles(): array
    {
        return array_column(session('import_groups') ?? [], 'title');
    }

    public function test_new_students_are_enrolled_into_the_section_of_their_year(): void
    {
        $this->import(
            "24-0101,Santos,Ana,,Female,{$this->yearLevel},regular,,{$this->letter}",
            "24-0102,Reyes,Ben,,Male,{$this->yearLevel},regular,,{$this->term->section->year_number}-{$this->letter}"
        )->assertRedirect(route('program-head.students.index'))
         ->assertSessionHas('success', '2 students imported. 2 enrolled into their sections.');

        foreach (['24-0101', '24-0102'] as $number) {
            $student = Student::where('student_number', $number)->firstOrFail();
            $this->assertDatabaseHas('enrollments', [
                'student_id' => $student->id, 'section_term_id' => $this->term->id, 'type' => Enrollment::TYPE_REGULAR, 'status' => Enrollment::STATUS_ENROLLED,
            ]);
        }
    }

    public function test_an_empty_section_adds_the_student_without_a_section(): void
    {
        $this->import("24-0103,Cruz,Lea,,Female,{$this->yearLevel},regular,,")
            ->assertSessionHas('success', '1 student imported. 1 not placed in a section (the section cell was empty).');

        $student = Student::where('student_number', '24-0103')->firstOrFail();
        $this->assertDatabaseMissing('enrollments', ['student_id' => $student->id]);
    }

    public function test_a_section_that_does_not_exist_is_reported_and_nothing_is_saved(): void
    {
        $this->import("24-0104,Lopez,Mia,,Female,{$this->yearLevel},regular,,Z")->assertSessionHas('import_groups');

        $this->assertSame(['Section not found'], $this->groupTitles());
        $this->assertStringContainsString('Row 2:', session('import_groups')[0]['items'][0]);
        $this->assertDatabaseMissing('students', ['student_number' => '24-0104']);
    }

    public function test_a_section_of_another_year_is_reported(): void
    {
        $otherYear = $this->term->section->year_number === '5' ? '4' : '5';

        $this->import("24-0105,Diaz,Joy,,Female,{$this->yearLevel},regular,,{$otherYear}-{$this->letter}");

        $this->assertSame(['Section does not match the year level'], $this->groupTitles());
        $this->assertDatabaseMissing('students', ['student_number' => '24-0105']);
    }

    public function test_a_section_without_an_active_term_is_reported(): void
    {
        Section::create(['program_id' => $this->programHead->program_id, 'year_number' => '2', 'section_letter' => 'Q', 'year_level' => '2nd Year', 'status' => 'active']);

        $this->import('24-0106,Gomez,Tom,,Male,2nd Year,regular,,Q');

        $this->assertSame(['Section has no active term'], $this->groupTitles());
        $this->assertDatabaseMissing('students', ['student_number' => '24-0106']);
    }

    public function test_a_locked_class_list_is_reported(): void
    {
        GradeVerification::create(['section_term_id' => $this->term->id, 'subject_id' => Subject::firstOrFail()->id, 'status' => 'pending']);

        $this->import("24-0107,Perez,Sam,,Male,{$this->yearLevel},regular,,{$this->letter}");

        $this->assertSame(['Class list locked'], $this->groupTitles());
        $this->assertDatabaseMissing('students', ['student_number' => '24-0107']);
    }

    public function test_every_kind_of_problem_is_reported_in_one_pass(): void
    {
        $this->import(
            "24-0108,Bad,Gender,,Unknown,{$this->yearLevel},regular,,{$this->letter}",
            "24-0109,Lost,Section,,Male,{$this->yearLevel},regular,,Z"
        );

        $this->assertSame(['Rows with problems', 'Section not found'], $this->groupTitles());
        $this->assertDatabaseMissing('students', ['student_number' => '24-0108']);
        $this->assertDatabaseMissing('students', ['student_number' => '24-0109']);
    }

    public function test_existing_students_are_skipped_and_never_moved(): void
    {
        $existing = Student::firstOrFail();
        $before   = Enrollment::where('student_id', $existing->id)->pluck('section_term_id')->all();

        $this->import(
            "{$existing->student_number},Same,Student,,Male,{$this->yearLevel},regular,,{$this->letter}",
            "24-0110,New,Student,,Male,{$this->yearLevel},regular,,{$this->letter}"
        )->assertSessionHas('success');

        $this->assertStringContainsString('1 skipped because the student number already exists', session('success'));
        $this->assertSame($before, Enrollment::where('student_id', $existing->id)->pluck('section_term_id')->all());
    }

    public function test_the_problem_dialog_lists_each_group(): void
    {
        $this->withSession(['import_groups' => [
            ['title' => 'Section not found', 'hint' => 'Create the section first.', 'items' => ['Row 2: BSCS 1-Z does not exist.']],
        ]])->actingAs($this->programHead)->get(route('program-head.students.index'))
            ->assertOk()
            ->assertSee('id="importErrorsDialog"', false)
            ->assertSee('Nothing was imported')
            ->assertSee('Section not found')
            ->assertSee('Row 2: BSCS 1-Z does not exist.');
    }

    public function test_section_labels_are_understood(): void
    {
        $this->assertSame([null, 'A'], ImportPlacement::parse('a'));
        $this->assertSame(['1', 'B'], ImportPlacement::parse('1-B'));
        $this->assertSame(['2', 'C'], ImportPlacement::parse('2 c'));
        $this->assertSame(['3', 'A'], ImportPlacement::parse('BSIT 3-A', 'BSIT'));
        $this->assertNull(ImportPlacement::parse('AB'));
        $this->assertNull(ImportPlacement::parse('6-A'));
        $this->assertNull(ImportPlacement::parse(''));
    }
}
