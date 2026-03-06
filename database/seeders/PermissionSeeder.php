<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Role Management Permissions
        Permission::create([
            'name' => 'view-roles',
            'guard_name' => 'web',
            'category' => 'Role Management',
            'description' => 'View list of all roles in the system',
        ]);

        Permission::create([
            'name' => 'create-role',
            'guard_name' => 'web',
            'category' => 'Role Management',
            'description' => 'Create new custom roles',
        ]);

        Permission::create([
            'name' => 'edit-role',
            'guard_name' => 'web',
            'category' => 'Role Management',
            'description' => 'Edit existing role names and descriptions',
        ]);

        Permission::create([
            'name' => 'delete-role',
            'guard_name' => 'web',
            'category' => 'Role Management',
            'description' => 'Delete roles that are not system-protected',
        ]);

        Permission::create([
            'name' => 'assign-permissions',
            'guard_name' => 'web',
            'category' => 'Role Management',
            'description' => 'Assign or remove permissions from roles',
        ]);

        Permission::create([
            'name' => 'assign-roles',
            'guard_name' => 'web',
            'category' => 'Role Management',
            'description' => 'Assign or remove roles from users',
        ]);

        Permission::create([
            'name' => 'view-audit-logs',
            'guard_name' => 'web',
            'category' => 'Role Management',
            'description' => 'View audit trail of role and permission changes',
        ]);

        // Leave Management Permissions (Placeholder for future features)
        Permission::create([
            'name' => 'view-leave-requests',
            'guard_name' => 'web',
            'category' => 'Leave Management',
            'description' => 'View leave requests',
        ]);

        Permission::create([
            'name' => 'create-leave-request',
            'guard_name' => 'web',
            'category' => 'Leave Management',
            'description' => 'Create own leave requests',
        ]);

        Permission::create([
            'name' => 'approve-leave-requests',
            'guard_name' => 'web',
            'category' => 'Leave Management',
            'description' => 'Approve or reject leave requests',
        ]);

        // Employee Management Permissions (Placeholder for future features)
        Permission::create([
            'name' => 'view-employees',
            'guard_name' => 'web',
            'category' => 'Employee Management',
            'description' => 'View employee list and details',
        ]);

        Permission::create([
            'name' => 'manage-employees',
            'guard_name' => 'web',
            'category' => 'Employee Management',
            'description' => 'Create, edit, and manage employee records',
        ]);

        // System Settings Permissions (Placeholder for future features)
        Permission::create([
            'name' => 'view-settings',
            'guard_name' => 'web',
            'category' => 'System Settings',
            'description' => 'View system settings',
        ]);

        Permission::create([
            'name' => 'manage-settings',
            'guard_name' => 'web',
            'category' => 'System Settings',
            'description' => 'Modify system settings and configurations',
        ]);
    }
}
