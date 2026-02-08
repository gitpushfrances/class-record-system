<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Student, Subject, Section, Enrollment};

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create Sample Students
        $students = [
            ['student_number' => '2021-00001', 'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'year_level' => '3rd Year', 'program' => 'BSCS'],
            ['student_number' => '2021-00002', 'first_name' => 'Maria', 'last_name' => 'Santos', 'year_level' => '3rd Year', 'program' => 'BSCS'],
            ['student_number' => '2021-00003', 'first_name' => 'Jose', 'last_name' => 'Reyes', 'year_level' => '3rd Year', 'program' => 'BSCS'],
            ['student_number' => '2021-00004', 'first_name' => 'Ana', 'last_name' => 'Garcia', 'year_level' => '3rd Year', 'program' => 'BSCS'],
            ['student_number' => '2021-00005', 'first_name' => 'Pedro', 'last_name' => 'Ramos', 'year_level' => '3rd Year', 'program' => 'BSCS'],
        ];

        foreach ($students as $student) {
            Student::create([
                'student_number' => $student['student_number'],
                'first_name' => $student['first_name'],
                'middle_name' => 'M.',
                'last_name' => $student['last_name'],
                'email' => strtolower($student['first_name'] . '.' . $student['last_name'] . '@student.test'),
                'year_level' => $student['year_level'],
                'program' => $student['program'],
                'status' => 'active',
            ]);
        }

        echo "Sample students created successfully!\n";

        // Create Sample Subjects
        $subjects = [
            ['code' => 'CS101', 'name' => 'Introduction to Programming', 'units' => 3],
            ['code' => 'CS102', 'name' => 'Data Structures and Algorithms', 'units' => 3],
            ['code' => 'CS103', 'name' => 'Database Management Systems', 'units' => 3],
            ['code' => 'MATH101', 'name' => 'Calculus I', 'units' => 3],
            ['code' => 'ENG101', 'name' => 'Technical Writing', 'units' => 3],
        ];

        foreach ($subjects as $subject) {
            Subject::create([
                'code' => $subject['code'],
                'name' => $subject['name'],
                'description' => 'Sample description for ' . $subject['name'],
                'units' => $subject['units'],
                'status' => 'active',
            ]);
        }

        echo "Sample subjects created successfully!\n";

        // Create Sample Section (CS101-3A)
        $teacher = \App\Models\User::where('email', 'teacher@classrecord.test')->first();
        $subject = Subject::where('code', 'CS101')->first();

        if ($teacher && $subject) {
            $section = Section::create([
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'section_name' => '3A',
                'year_level' => '3rd Year',
                'semester' => '1st Semester',
                'academic_year' => '2024-2025',
                'schedule' => 'MWF 10:00-11:00 AM',
                'room' => 'CS Lab 1',
                'status' => 'active',
            ]);

            echo "Sample section created successfully!\n";

            // Enroll all students to this section
            $allStudents = Student::all();
            foreach ($allStudents as $student) {
                Enrollment::create([
                    'student_id' => $student->id,
                    'section_id' => $section->id,
                    'status' => 'enrolled',
                    'enrolled_at' => now(),
                ]);
            }

            echo "Students enrolled to section successfully!\n";
        }
    }
}
