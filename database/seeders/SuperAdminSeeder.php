<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@classrecord.test',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $superAdmin->assignRole('super_admin');

        echo "Super Admin created successfully!\n";
        echo "Email: admin@classrecord.test\n";
        echo "Password: password\n";

        // Create Sample Dean
        $dean = User::create([
            'name' => 'Dean Sample',
            'email' => 'dean@classrecord.test',
            'password' => Hash::make('password'),
            'role' => 'dean',
            'status' => 'active',
            'approved_by' => $superAdmin->id,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        $dean->assignRole('dean');

        echo "Sample Dean created successfully!\n";
        echo "Email: dean@classrecord.test\n";
        echo "Password: password\n";

        // Create Sample Teacher (Active)
        $teacher = User::create([
            'name' => 'Teacher Sample',
            'email' => 'teacher@classrecord.test',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'status' => 'active',
            'approved_by' => $dean->id,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        $teacher->assignRole('teacher');

        echo "Sample Teacher created successfully!\n";
        echo "Email: teacher@classrecord.test\n";
        echo "Password: password\n";

        // Create Pending Teacher
        $pendingTeacher = User::create([
            'name' => 'Pending Teacher',
            'email' => 'pending@classrecord.test',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'status' => 'pending',
            'email_verified_at' => now(),
        ]);

        $pendingTeacher->assignRole('teacher');

        echo "Pending Teacher created successfully!\n";
        echo "Email: pending@classrecord.test\n";
        echo "Password: password\n";
    }
}
