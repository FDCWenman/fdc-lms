<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Role Management Permissions
        Permission::firstOrCreate(
            ['name' => 'view-roles', 'guard_name' => 'web'],
            [
                'category' => 'Role Management',
                'description' => 'View list of all roles in the system',
            ]
        );

        Permission::firstOrCreate(
            ['name' => 'create-role', 'guard_name' => 'web'],
            [
                'category' => 'Role Management',
                'description' => 'Create new custom roles',
            ]
        );

        Permission::firstOrCreate(
            ['name' => 'edit-role', 'guard_name' => 'web'],
            [
                'category' => 'Role Management',
                'description' => 'Edit existing role names and descriptions',
            ]
        );

        Permission::firstOrCreate(
            ['name' => 'delete-role', 'guard_name' => 'web'],
            [
                'category' => 'Role Management',
                'description' => 'Delete roles that are not system-protected',
            ]
        );

        Permission::firstOrCreate(
            ['name' => 'assign-permissions', 'guard_name' => 'web'],
            [
                'category' => 'Role Management',
                'description' => 'Assign or remove permissions from roles',
            ]
        );

        Permission::firstOrCreate(
            ['name' => 'assign-roles', 'guard_name' => 'web'],
            [
                'category' => 'Role Management',
                'description' => 'Assign or remove roles from users',
            ]
        );

        Permission::firstOrCreate(
            ['name' => 'view-audit-logs', 'guard_name' => 'web'],
            [
                'category' => 'Role Management',
                'description' => 'View audit trail of role and permission changes',
            ]
        );

        // Leave Management Permissions (Placeholder for future features)
        Permission::firstOrCreate(
            ['name' => 'view-leave-requests', 'guard_name' => 'web'],
            [
                'category' => 'Leave Management',
                'description' => 'View leave requests',
            ]
        );

        Permission::firstOrCreate(
            ['name' => 'create-leave-request', 'guard_name' => 'web'],
            [
                'category' => 'Leave Management',
                'description' => 'Create own leave requests',
            ]
        );

        Permission::firstOrCreate(
            ['name' => 'approve-leave-requests', 'guard_name' => 'web'],
            [
                'category' => 'Leave Management',
                'description' => 'Approve or reject leave requests',
            ]
        );

        // Employee Management Permissions (Placeholder for future features)
        Permission::firstOrCreate(
            ['name' => 'view-employees', 'guard_name' => 'web'],
            [
                'category' => 'Employee Management',
                'description' => 'View employee list and details',
            ]
        );

        Permission::firstOrCreate(
            ['name' => 'manage-employees', 'guard_name' => 'web'],
            [
                'category' => 'Employee Management',
                'description' => 'Create, edit, and manage employee records',
            ]
        );

        // System Settings Permissions (Placeholder for future features)
        Permission::firstOrCreate(
            ['name' => 'view-settings', 'guard_name' => 'web'],
            [
                'category' => 'System Settings',
                'description' => 'View system settings',
            ]
        );

        Permission::firstOrCreate(
            ['name' => 'manage-settings', 'guard_name' => 'web'],
            [
                'category' => 'System Settings',
                'description' => 'Modify system settings and configurations',
            ]
        );
    }
}
