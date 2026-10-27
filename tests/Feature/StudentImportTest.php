<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private User $programHead;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
    }

    private function csv(string ...$rows): UploadedFile
    {
        $lines = array_merge(['student_number,last_name,first_name,middle_name,gender,year_level,student_type,email'], $rows);

        return UploadedFile::fake()->createWithContent('students.csv', implode("\n", $lines) . "\n");
    }

    public function test_program_head_imports_students_into_their_program(): void
    {
        $file = $this->csv(
            '24-0001,Dela Cruz,Juan,Santos,Male,1st Year,regular,juan@example.com',
            '24-0002,Reyes,Ana,,F,1,,'
        );

        $this->actingAs($this->programHead)->post(route('program-head.students.import'), ['file' => $file])
            ->assertRedirect(route('program-head.students.index'))
            ->assertSessionHas('success', '2 students imported.');

        $this->assertDatabaseHas('students', ['student_number' => '24-0001', 'gender' => 'male', 'program_id' => $this->programHead->program_id, 'status' => 'active']);
        $this->assertDatabaseHas('students', ['student_number' => '24-0002', 'gender' => 'female', 'year_level' => '1st Year', 'student_type' => 'regular', 'email' => null]);
    }

    public function test_existing_student_numbers_are_skipped_and_reported(): void
    {
        $existing = Student::firstOrFail();

        $this->actingAs($this->programHead)->post(route('program-head.students.import'), ['file' => $this->csv(
            "{$existing->student_number},Changed,Name,,Male,2nd Year,regular,",
            '24-0003,Lopez,Mark,,Male,2nd Year,regular,'
        )])->assertSessionHas('success', "1 student imported. 1 skipped because the student number already exists: {$existing->student_number}.");

        $this->assertDatabaseHas('students', ['id' => $existing->id, 'last_name' => $existing->last_name]);
        $this->assertDatabaseHas('students', ['student_number' => '24-0003']);
    }

    public function test_file_with_only_existing_student_numbers_is_an_error_not_a_success(): void
    {
        $existing = Student::firstOrFail();

        $this->actingAs($this->programHead)->post(route('program-head.students.import'), ['file' => $this->csv(
            "{$existing->student_number},Changed,Name,,Male,2nd Year,regular,"
        )])->assertSessionMissing('success')
          ->assertSessionHas('import_errors', ["Nothing was imported: every student number in this file already exists ({$existing->student_number})."]);

        $this->assertDatabaseHas('students', ['id' => $existing->id, 'last_name' => $existing->last_name]);
    }

    public function test_any_invalid_row_imports_nothing(): void
    {
        $this->actingAs($this->programHead)->post(route('program-head.students.import'), ['file' => $this->csv(
            '24-0004,Valid,Row,,Male,3rd Year,regular,',
            '2400005,Bad,Number,,Unknown,9th Year,regular,',
            '24-0004,Repeat,Row,,Male,3rd Year,regular,'
        )])->assertRedirect()->assertSessionHas('import_errors');

        $errors = session('import_errors');
        $this->assertContains('Row 3: student number must follow the format 00-0000.', $errors);
        $this->assertContains('Row 3: gender must be Male or Female.', $errors);
        $this->assertContains('Row 4: student number 24-0004 is repeated (first on row 2).', $errors);
        $this->assertDatabaseMissing('students', ['student_number' => '24-0004']);
    }

    public function test_missing_required_column_is_reported(): void
    {
        $file = UploadedFile::fake()->createWithContent('students.csv', "student_number,last_name,first_name\n24-0006,Cruz,Ben\n");

        $this->actingAs($this->programHead)->post(route('program-head.students.import'), ['file' => $file])
            ->assertSessionHas('import_errors', ['Missing column(s): gender, year_level. Use the template headers.']);
    }

    public function test_dean_cannot_import_students(): void
    {
        $dean = User::where('email', 'dean@classrecord.test')->firstOrFail();
        $bsit = Program::where('code', 'BSIT')->firstOrFail();

        $this->assertFalse(\Illuminate\Support\Facades\Route::has('dean.students.import'));
        $this->actingAs($dean)->post('/dean/students/import', ['program_id' => $bsit->id, 'file' => $this->csv('24-0007,Garcia,Lea,,Female,1st Year,regular,')])
            ->assertNotFound();
        $this->assertDatabaseMissing('students', ['student_number' => '24-0007']);
    }

    public function test_template_downloads_the_column_headers(): void
    {
        $response = $this->actingAs($this->programHead)->get(route('program-head.students.template'))->assertOk();

        $this->assertSame("student_number,last_name,first_name,middle_name,gender,year_level,student_type,email\r\n", $response->getContent());
        $this->assertStringContainsString('student-import-template.csv', $response->headers->get('Content-Disposition'));
    }
}
