<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::create([
            'name'              => 'Super Admin',
            'email'             => 'admin@classrecord.test',
            'password'          => Hash::make('password'),
            'role'              => 'super_admin',
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super_admin');
        echo "Super Admin created successfully!\nEmail: admin@classrecord.test\nPassword: password\n";

        // Departments
        $ccs = Department::create(['name' => 'College of Computer Studies', 'code' => 'CCS']);
        $cba = Department::create(['name' => 'College of Business Administration', 'code' => 'CBA']);
        $coe = Department::create(['name' => 'College of Engineering', 'code' => 'COE']);
        echo "Sample departments created!\n";

        $dean = User::create([
            'name'              => 'Dean Sample',
            'email'             => 'dean@classrecord.test',
            'password'          => Hash::make('password'),
            'role'              => 'dean',
            'status'            => 'active',
            'department_id'     => $ccs->id,
            'approved_by'       => $superAdmin->id,
            'approved_at'       => now(),
            'email_verified_at' => now(),
        ]);
        $dean->assignRole('dean');
        echo "Sample Dean created successfully!\nEmail: dean@classrecord.test\nPassword: password\n";

        $teacher = User::create([
            'name'              => 'Teacher Sample',
            'email'             => 'teacher@classrecord.test',
            'password'          => Hash::make('password'),
            'role'              => 'teacher',
            'status'            => 'active',
            'department_id'     => $ccs->id,
            'approved_by'       => $dean->id,
            'approved_at'       => now(),
            'email_verified_at' => now(),
        ]);
        $teacher->assignRole('teacher');
        echo "Sample Teacher created successfully!\nEmail: teacher@classrecord.test\nPassword: password\n";

        $programHead = User::create([
            'name'              => 'Program Head Sample',
            'email'             => 'programhead@classrecord.test',
            'password'          => Hash::make('password'),
            'role'              => 'program_head',
            'status'            => 'active',
            'department_id'     => $ccs->id,
            'approved_by'       => $superAdmin->id,
            'approved_at'       => now(),
            'email_verified_at' => now(),
        ]);
        $programHead->assignRole('program_head');
        echo "Sample Program Head created!\nEmail: programhead@classrecord.test\nPassword: password\n";

        $pendingTeacher = User::create([
            'name'              => 'Pending Teacher',
            'email'             => 'pending@classrecord.test',
            'password'          => Hash::make('password'),
            'role'              => 'teacher',
            'status'            => 'pending',
            'department_id'     => $ccs->id,
            'email_verified_at' => now(),
        ]);
        $pendingTeacher->assignRole('teacher');
        echo "Pending Teacher created successfully!\nEmail: pending@classrecord.test\nPassword: password\n";

        // CBA — second department for negative-testing department isolation
        $cba = Department::where('code', 'CBA')->first();

        $deanCba = User::create([
            'name'              => 'Dean CBA Sample',
            'email'             => 'dean.cba@classrecord.test',
            'password'          => Hash::make('password'),
            'role'              => 'dean',
            'status'            => 'active',
            'department_id'     => $cba->id,
            'approved_by'       => $superAdmin->id,
            'approved_at'       => now(),
            'email_verified_at' => now(),
        ]);
        $deanCba->assignRole('dean');
        echo "Sample CBA Dean created!\nEmail: dean.cba@classrecord.test\nPassword: password\n";

        $teacherCba = User::create([
            'name'              => 'Teacher CBA Sample',
            'email'             => 'teacher.cba@classrecord.test',
            'password'          => Hash::make('password'),
            'role'              => 'teacher',
            'status'            => 'active',
            'department_id'     => $cba->id,
            'approved_by'       => $deanCba->id,
            'approved_at'       => now(),
            'email_verified_at' => now(),
        ]);
        $teacherCba->assignRole('teacher');
        echo "Sample CBA Teacher created!\nEmail: teacher.cba@classrecord.test\nPassword: password\n";

        $programHeadCba = User::create([
            'name'              => 'Program Head CBA Sample',
            'email'             => 'programhead.cba@classrecord.test',
            'password'          => Hash::make('password'),
            'role'              => 'program_head',
            'status'            => 'active',
            'department_id'     => $cba->id,
            'approved_by'       => $superAdmin->id,
            'approved_at'       => now(),
            'email_verified_at' => now(),
        ]);
        $programHeadCba->assignRole('program_head');
        echo "Sample CBA Program Head created!\nEmail: programhead.cba@classrecord.test\nPassword: password\n";
    }
}
