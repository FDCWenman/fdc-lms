<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run seeders
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /** @test */
    public function administrator_can_view_roles_list()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $response = $this->actingAs($admin)->get(route('admin.roles.index'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(\App\Livewire\Roles\ManageRoles::class);
    }

    /** @test */
    public function non_administrator_cannot_access_role_management()
    {
        $user = User::factory()->create();
        $user->assignRole('Employee');

        $response = $this->actingAs($user)->get(route('admin.roles.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function administrator_can_create_role_with_valid_name()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ManageRoles::class)
            ->call('createRole', [
                'name' => 'Team Lead',
                'description' => 'Leads a development team',
            ])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'name' => 'Team Lead',
            'description' => 'Leads a development team',
        ]);
    }

    /** @test */
    public function role_name_is_required()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ManageRoles::class)
            ->call('createRole', [
                'name' => '',
                'description' => 'Some description',
            ])
            ->assertHasErrors(['name' => 'required']);
    }

    /** @test */
    public function role_name_must_be_max_50_characters()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ManageRoles::class)
            ->call('createRole', [
                'name' => str_repeat('a', 51),
                'description' => 'Some description',
            ])
            ->assertHasErrors(['name' => 'max']);
    }

    /** @test */
    public function role_name_must_match_regex_pattern()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ManageRoles::class)
            ->call('createRole', [
                'name' => 'Invalid@Name!',
                'description' => 'Some description',
            ])
            ->assertHasErrors(['name' => 'regex']);
    }

    /** @test */
    public function role_name_must_be_unique()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        Role::create([
            'name' => 'Existing Role',
            'guard_name' => 'web',
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ManageRoles::class)
            ->call('createRole', [
                'name' => 'Existing Role',
                'description' => 'Some description',
            ])
            ->assertHasErrors(['name' => 'unique']);
    }

    /** @test */
    public function description_is_optional()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ManageRoles::class)
            ->call('createRole', [
                'name' => 'Simple Role',
                'description' => null,
            ])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'name' => 'Simple Role',
            'description' => null,
        ]);
    }

    /** @test */
    public function description_must_be_max_500_characters()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ManageRoles::class)
            ->call('createRole', [
                'name' => 'Test Role',
                'description' => str_repeat('a', 501),
            ])
            ->assertHasErrors(['description' => 'max']);
    }

    /** @test */
    public function creating_role_creates_activity_log()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ManageRoles::class)
            ->call('createRole', [
                'name' => 'Logged Role',
                'description' => 'This should be logged',
            ]);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'created',
            'causer_id' => $admin->id,
            'causer_type' => User::class,
        ]);
    }

    /** @test */
    public function roles_list_shows_user_count()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web']);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole($role);
        $user2->assignRole($role);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ManageRoles::class)
            ->assertSee('Test Role')
            ->assertSee('2'); // Should show user count
    }

    /** @test */
    public function cannot_delete_protected_role()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $adminRole = Role::where('name', 'Administrator')->first();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ManageRoles::class)
            ->call('deleteRole', $adminRole->id)
            ->assertHasErrors(['role']);
    }

    /** @test */
    public function can_delete_non_protected_role()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $role = Role::create([
            'name' => 'Deletable Role',
            'guard_name' => 'web',
            'is_protected' => false,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ManageRoles::class)
            ->call('deleteRole', $role->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('roles', [
            'name' => 'Deletable Role',
        ]);
    }
}
