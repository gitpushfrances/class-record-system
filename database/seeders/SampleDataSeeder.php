<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{Student, Subject, Section, Enrollment, Department, Program};
use App\Models\SectionTerm;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Students
        $students = [
            ['student_number' => '2021-00001', 'first_name' => 'Juan',  'last_name' => 'Dela Cruz'],
            ['student_number' => '2021-00002', 'first_name' => 'Maria', 'last_name' => 'Santos'],
            ['student_number' => '2021-00003', 'first_name' => 'Jose',  'last_name' => 'Reyes'],
            ['student_number' => '2021-00004', 'first_name' => 'Ana',   'last_name' => 'Garcia'],
            ['student_number' => '2021-00005', 'first_name' => 'Pedro', 'last_name' => 'Ramos'],
        ];
        foreach ($students as $s) {
            Student::create([
                'student_number' => $s['student_number'],
                'first_name'     => $s['first_name'],
                'middle_name'    => 'M.',
                'last_name'      => $s['last_name'],
                'email'          => strtolower($s['first_name'] . '.' . $s['last_name'] . '@student.test'),
                'year_level'     => '3rd Year',
                'program'        => 'BSCS',
                'student_type'   => 'regular',
                'status'         => 'active',
            ]);
        }
        echo "Sample students created!\n";

        // Programs under CCS
        $ccs  = Department::where('code', 'CCS')->first();
        $dean  = \App\Models\User::where('email', 'dean@classrecord.test')->first();
        $admin = \App\Models\User::where('email', 'admin@classrecord.test')->first();

        $programs = [
            ['code' => 'BSCS',  'name' => 'BS Computer Science'],
            ['code' => 'BSIT',  'name' => 'BS Information Technology'],
            ['code' => 'BSCpE', 'name' => 'BS Computer Engineering'],
        ];
        foreach ($programs as $p) {
            Program::create([
                'department_id' => $ccs->id,
                'code'          => $p['code'],
                'name'          => $p['name'],
                'status'        => 'approved',
                'requested_by'  => $dean?->id,
                'approved_by'   => $admin?->id,
                'approved_at'   => now(),
            ]);
        }
        echo "Sample programs created!\n";

        // Subjects
        $subjects = [
            ['code' => 'CS101',   'name' => 'Introduction to Programming',   'units' => 3],
            ['code' => 'CS102',   'name' => 'Data Structures and Algorithms', 'units' => 3],
            ['code' => 'CS103',   'name' => 'Database Management Systems',    'units' => 3],
            ['code' => 'MATH101', 'name' => 'Calculus I',                     'units' => 3],
            ['code' => 'ENG101',  'name' => 'Technical Writing',              'units' => 3],
        ];
        foreach ($subjects as $sub) {
            Subject::create([
                'code'         => $sub['code'],
                'name'         => $sub['name'],
                'description'  => 'Sample description for ' . $sub['name'],
                'units'        => $sub['units'],
                'department'   => 'Computer Science',
                'status'       => 'approved',
                'requested_by' => $dean?->id,
                'approved_by'  => $admin?->id,
                'approved_at'  => now(),
            ]);
        }
        echo "Sample subjects created!\n";

        // Section
        $bscs    = Program::where('code', 'BSCS')->first();
        $teacher = \App\Models\User::where('email', 'teacher@classrecord.test')->first();

        $section = Section::create([
            'program_id'     => $bscs->id,
            'year_number'    => '3',
            'section_letter' => 'A',
            'year_level'     => '3rd Year',
            'status'         => 'active',
        ]);
        echo "Sample section created!\n";

        $term = SectionTerm::create([
            'section_id'    => $section->id,
            'adviser_id'    => $teacher?->id,
            'academic_year' => '2024-2025',
            'semester'      => '1st Semester',
            'status'        => 'active',
        ]);
        echo "Sample section term created!\n";

        $allStudents = Student::all();
        foreach ($allStudents as $student) {
            Enrollment::create([
                'student_id'      => $student->id,
                'section_term_id' => $term->id,
                'status'          => 'enrolled',
                'enrolled_at'     => now(),
            ]);
        }
        echo "Students enrolled!\n";
    }
}
