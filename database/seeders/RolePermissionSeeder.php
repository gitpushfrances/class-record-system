<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'manage users',
            'approve teachers',
            'manage deans',

            // Student management
            'manage students',
            'view students',
            'import students',

            // Subject management
            'manage subjects',
            'view subjects',

            // Section management
            'manage sections',
            'assign teachers',
            'view sections',

            // Grade management
            'configure grades',
            'enter grades',
            'view grades',
            'lock grades',
            'approve grade config',

            // Attendance
            'enter attendance',
            'view attendance',

            // Reports
            'view all reports',
            'view own reports',
            'export data',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - Full access
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Dean - Administrative access
        $dean = Role::create(['name' => 'dean']);
        $dean->givePermissionTo([
            'approve teachers',
            'manage students',
            'view students',
            'import students',
            'manage subjects',
            'view subjects',
            'manage sections',
            'assign teachers',
            'view sections',
            'approve grade config',
            'view grades',
            'view all reports',
            'export data',
        ]);

        // Teacher - Class management
        $teacher = Role::create(['name' => 'teacher']);
        $teacher->givePermissionTo([
            'view students',
            'view subjects',
            'view sections',
            'configure grades',
            'enter grades',
            'view grades',
            'lock grades',
            'enter attendance',
            'view attendance',
            'view own reports',
            'export data',
        ]);

        echo "Roles and permissions created successfully!\n";
    }
}
