<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create Administrator role (protected)
        $adminRole = Role::firstOrCreate(
            ['name' => 'Administrator', 'guard_name' => 'web'],
            [
                'description' => 'Full system access with all permissions',
                'is_protected' => true,
            ]
        );

        // Assign all permissions to Administrator role
        $adminRole->syncPermissions(Permission::all());

        // Create Employee role (basic permissions)
        $employeeRole = Role::firstOrCreate(
            ['name' => 'Employee', 'guard_name' => 'web'],
            [
                'description' => 'Standard employee with basic leave management permissions',
                'is_protected' => false,
            ]
        );

        $employeeRole->syncPermissions([
            'view-leave-requests',
            'create-leave-request',
        ]);

        // Create Manager role
        $managerRole = Role::firstOrCreate(
            ['name' => 'Manager', 'guard_name' => 'web'],
            [
                'description' => 'Team manager with leave approval permissions',
                'is_protected' => false,
            ]
        );

        $managerRole->syncPermissions([
            'view-leave-requests',
            'create-leave-request',
            'approve-leave-requests',
            'view-employees',
        ]);

        // Create existing roles without descriptions (backward compatibility)
        $existingRoles = [
            ['name' => 'hr', 'guard_name' => 'web'],
            ['name' => 'team-lead', 'guard_name' => 'web'],
            ['name' => 'project-manager', 'guard_name' => 'web'],
        ];

        foreach ($existingRoles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']]
            );
        }

        $this->command->info('Roles seeded successfully: Administrator, Employee, Manager, and existing roles');
    }
}
