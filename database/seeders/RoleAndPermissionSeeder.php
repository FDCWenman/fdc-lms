<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Leave Management Permissions
            'view_own_leaves' => 'View own leave requests',
            'create_leave_request' => 'Create leave requests',
            'cancel_own_leave' => 'Cancel own leave requests',
            
            // Approval Permissions
            'approve_leaves' => 'Approve leave requests',
            'view_team_leaves' => 'View team leave requests',
            
            // Account Management Permissions (HR)
            'manage_accounts' => 'Manage employee accounts',
            'view_all_accounts' => 'View all employee accounts',
            'deactivate_accounts' => 'Deactivate employee accounts',
            
            // Reporting Permissions
            'view_leave_reports' => 'View leave reports and analytics',
            
            // Calendar Permissions
            'view_portal_calendar' => 'View portal calendar with all leaves',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
        }

        // Create roles with IDs as per spec
        // Role ID 1: Employee
        $employee = Role::firstOrCreate(
            ['name' => 'Employee', 'guard_name' => 'web'],
            ['id' => 1]
        );
        $employee->syncPermissions([
            'view_own_leaves',
            'create_leave_request',
            'cancel_own_leave',
        ]);

        // Role ID 2: HR Approver
        $hrApprover = Role::firstOrCreate(
            ['name' => 'HR Approver', 'guard_name' => 'web'],
            ['id' => 2]
        );
        $hrApprover->syncPermissions([
            'view_own_leaves',
            'create_leave_request',
            'cancel_own_leave',
            'approve_leaves',
            'view_team_leaves',
            'manage_accounts',
            'view_all_accounts',
            'deactivate_accounts',
            'view_leave_reports',
            'view_portal_calendar',
        ]);

        // Role ID 3: Lead Approver (Team Lead)
        $leadApprover = Role::firstOrCreate(
            ['name' => 'Lead Approver', 'guard_name' => 'web'],
            ['id' => 3]
        );
        $leadApprover->syncPermissions([
            'view_own_leaves',
            'create_leave_request',
            'cancel_own_leave',
            'approve_leaves',
            'view_team_leaves',
            'view_portal_calendar',
        ]);

        // Role ID 4: PM Approver (Project Manager)
        $pmApprover = Role::firstOrCreate(
            ['name' => 'PM Approver', 'guard_name' => 'web'],
            ['id' => 4]
        );
        $pmApprover->syncPermissions([
            'view_own_leaves',
            'create_leave_request',
            'cancel_own_leave',
            'approve_leaves',
            'view_team_leaves',
            'view_portal_calendar',
        ]);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Roles:');
        $this->command->info('  1. Employee (Basic leave management)');
        $this->command->info('  2. HR Approver (Full account and leave management)');
        $this->command->info('  3. Lead Approver (Team lead - approval and calendar access)');
        $this->command->info('  4. PM Approver (Project manager - approval and calendar access)');
    }
}
