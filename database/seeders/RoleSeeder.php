<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles for the authentication system
        $roles = [
            ['name' => 'employee', 'guard_name' => 'web'],
            ['name' => 'hr', 'guard_name' => 'web'],
            ['name' => 'team-lead', 'guard_name' => 'web'],
            ['name' => 'project-manager', 'guard_name' => 'web'],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']]
            );
        }

        $this->command->info('Roles seeded successfully: employee, hr, team-lead, project-manager');
    }
}
