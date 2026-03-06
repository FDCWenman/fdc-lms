<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ensure roles exist
        $roles = ['employee', 'hr', 'team-lead', 'project-manager'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Create HR admin user for testing registration
        $hrUser = User::firstOrCreate(
            ['email' => 'hr@example.com'],
            [
                'name' => 'HR Admin',
                'password' => Hash::make('password'),
                'slack_id' => 'U123456789',
                'status' => 1, // active
                'verified_at' => now(),
            ]
        );
        $hrUser->syncRoles(['hr']);

        // Create regular employee user
        $employee = User::firstOrCreate(
            ['email' => 'employee@example.com'],
            [
                'name' => 'Test Employee',
                'password' => Hash::make('password'),
                'slack_id' => 'U987654321',
                'status' => 1, // active
                'verified_at' => now(),
            ]
        );
        $employee->syncRoles(['employee']);
    }
}
